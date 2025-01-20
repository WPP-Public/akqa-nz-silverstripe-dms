<?php

namespace Sunnysideup\DMS\Cms;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\CMS\Controllers\CMSMain;
use Sunnysideup\DMS\Model\DMSDocumentSet;
use SilverStripe\View\Requirements;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Control\Controller;
use SilverStripe\View\ArrayData;
use SilverStripe\Core\Convert;
use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Core\Config\Config;
use SilverStripe\Assets\File;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\Security;
use SilverStripe\Admin\LeftAndMain;
use Sunnysideup\DMS\Admin\DMSDocumentAdmin;

/**
 * @package dms
 */
class DMSDocumentAddController extends LeftAndMain
{
    private static $url_segment = 'pages/adddocument';
    private static $url_priority = 60;
    private static $required_permission_codes = 'CMS_ACCESS_AssetAdmin';
    private static $menu_title = 'Edit Page';
    private static $tree_class = SiteTree::class;
    private static $session_namespace = CMSMain::class;

    /**
     * Allowed file upload extensions, will be merged with `$allowed_extensions` from {@link File}
     *
     * @config
     * @var array
     */
    private static $allowed_extensions = [];

    private static $allowed_actions = array(
        'getEditForm',
        'documentautocomplete',
        'linkdocument',
        'documentlist'
    );

    /**
     * Custom currentPage() method to handle opening the 'root' folder
     *
     * @return SiteTree
     */
    public function currentPage()
    {
        $id = $this->currentPageID();
        if ($id === 0) {
            return SiteTree::singleton();
        }
        return parent::currentPage();
    }

    /**
     * Return fake-ID "root" if no ID is found (needed to upload files into the root-folder). Otherwise the page ID
     * is passed in from the {@link DMSGridFieldAddNewButton}.
     *
     * @return int
     */
    public function currentPageID()
    {
        return (int) $this->getRequest()->getVar('page_id');
    }

    /**
     * Get the current document set, if a document set ID was provided
     *
     * @return DMSDocumentSet
     */
    public function getCurrentDocumentSet()
    {
        if ($id = $this->getRequest()->getVar('dsid')) {
            return DMSDocumentSet::get()->byId($id);
        }

        return singleton(DMSDocumentSet::class);
    }

    /**
     * @return Form
     */
    public function getEditForm($id = null, $fields = null)
    {
        $documentSet = $this->getCurrentDocumentSet();
        if (!$documentSet) {
            throw new \RuntimeException('No document set found');
        }

        // Configure upload field
        $uploadField = DMSUploadField::create('AssetUploadField', '')
            ->setConfig('previewMaxWidth', 40)
            ->setConfig('previewMaxHeight', 30)
            ->setConfig('sequentialUploads', 1)
            ->addExtraClass('ss-assetuploadfield')
            ->removeExtraClass('ss-uploadfield')
            ->setTemplate('AssetUploadField')
            ->setRecord($documentSet);

        // Set allowed extensions
        $validator = $uploadField->getValidator();
        $validator->setAllowedExtensions($this->getAllowedExtensions());
        $extensions = $validator->getAllowedExtensions();
        asort($extensions);

        // Create back link button
        $backlink = $this->Backlink();
        $doneButton = LiteralField::create(
            'doneButton',
            sprintf(
                '<a class="ss-ui-button ss-ui-action-constructive cms-panel-link ui-corner-all" href="%s">%s</a>',
                $backlink,
                _t('UploadField.DONE', 'DONE')
            )
        );

        // Create add existing field
        $addExistingField = DMSDocumentAddExistingField::create(
            'AddExisting',
            _t('DMSDocumentAddExistingField.ADDEXISTING', 'Add Existing')
        )->setRecord($documentSet);

        // Create allowed extensions field
        $allowedExtensionsField = LiteralField::create(
            'AllowedExtensions',
            sprintf(
                '<p>%s: %s</p>',
                _t('AssetAdmin.ALLOWEDEXTS', 'Allowed extensions'),
                implode('<em>, </em>', $extensions)
            )
        );

        // Create tabs
        $tabSet = TabSet::create(
            _t('DMSDocumentAddController.MAINTAB', 'Main'),
            Tab::create(
                _t('UploadField.FROMCOMPUTER', 'From your computer'),
                $uploadField,
                $allowedExtensionsField
            ),
            Tab::create(
                _t('UploadField.FROMCMS', 'From the CMS'),
                $addExistingField
            )
        );

        // Create form
        $form = Form::create(
            $this,
            'getEditForm',
            FieldList::create($tabSet),
            FieldList::create($doneButton)
        );

        // Configure form
        $form->addExtraClass(sprintf('center cms-edit-form %s', $this->BaseCSSClasses()))
            ->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

        // Add hidden fields
        $form->Fields()->push(HiddenField::create('ID', false, $documentSet->ID));
        $form->Fields()->push(HiddenField::create('DSID', false, $documentSet->ID));

        // Set backlink
        $form->Backlink = $backlink;

        return $form;
    }

    /**
     * @return ArrayList
     */
    public function Breadcrumbs($unlinked = false)
    {
        $items = parent::Breadcrumbs($unlinked);

        // The root element should explicitly point to the root node.
        $items[0]->Link = Controller::join_links(singleton(CMSPageEditController::class)->Link('show'), 0);

        // Enforce linkage of hierarchy to AssetAdmin
        foreach ($items as $item) {
            $baselink = $this->Link('show');
            if (strpos($item->Link, $baselink) !== false) {
                $item->Link = str_replace($baselink, singleton(CMSPageEditController::class)->Link('show'), $item->Link);
            }
        }

        $items->push(new ArrayData(array(
            'Title' => _t('DMSDocumentSet.ADDDOCUMENTBUTTON', 'Add Document'),
            'Link' => $this->Link()
        )));

        return $items;
    }

    /**
     * Returns the link to be used to return the user after uploading a document. Scenarios:
     *
     * 1) Page context: page ID and document set ID provided, redirect back to the page and document set
     * 2) Document set context: no page ID, document set ID provided, redirect back to document set in ModelAdmin
     * 3) Document context: no page ID and no document set ID provided, redirect back to documents in ModelAdmin
     */
    public function Backlink(): string
    {
        if (!$this->getRequest()->getVar('dsid') || !$this->currentPageID()) {
            $admin = DMSDocumentAdmin::create();

            if ($this->getRequest()->getVar('dsid')) {
                return Controller::join_links(
                    $admin->Link(DMSDocumentSet::class),
                    'EditForm/field/DMSDocumentSet/item',
                    (int) $this->getRequest()->getVar('dsid'),
                    'edit'
                );
            }
            return $admin->Link();
        }

        return $this->getPageEditLink($this->currentPageID(), (int) $this->getRequest()->getVar('dsid'));
    }

    /**
     * Return a link to edit a page, deep linking into the document set given
     *
     * @param  int $pageId
     * @param  int $documentSetId
     * @return string
     */
    protected function getPageEditLink($pageId, $documentSetId)
    {
        return Controller::join_links(
            CMSPageEditController::singleton()->getEditForm($pageId)->FormAction(),
            'field/DocumentSets/item',
            (int) $documentSetId
        );
    }

    public function documentautocomplete()
    {
        $term = (string) $this->getRequest()->getVar('term');
        $termSql = Convert::raw2sql($term);
        $data = DMSDocument::get()
            ->where(
                '("ID" LIKE \'%' . $termSql . '%\' OR "Filename" LIKE \'%' . $termSql . '%\''
                    . ' OR "Title" LIKE \'%' . $termSql . '%\')'
            )
            ->sort('ID ASC')
            ->limit(20);

        $return = [];
        foreach ($data as $doc) {
            $return[] = array(
                'label' => $doc->ID . ' - ' . $doc->Title,
                'value' => $doc->ID
            );
        }

        return json_encode($return);
    }

    /**
     * Link an existing document to the given document set ID
     * @return string JSON
     */
    public function linkdocument()
    {
        $return = array('error' => _t('UploadField.FIELDNOTSET', 'Could not add document to page'));
        $documentSet = $this->getCurrentDocumentSet();
        if (!empty($documentSet)) {
            $document = DMSDocument::get()->byId($this->getRequest()->getVar('documentID'));
            $documentSet->Documents()->add($document);

            $buttonText = '<button class="ss-uploadfield-item-edit ss-ui-button ui-corner-all"'
                . ' title="' . _t('DMSDocument.EDITDOCUMENT', 'Edit this document') . '" data-icon="pencil">'
                . _t('DMSDocument.EDIT', 'Edit') . '<span class="toggle-details">'
                . '<span class="toggle-details-icon"></span></span></button>';

            // Collect all output data.
            $return = array(
                'id' => $document->ID,
                'name' => $document->getTitle(),
                'thumbnail_url' => $document->Icon($document->getExtension()),
                'edit_url' => $this->getEditForm()->Fields()->fieldByName('Main.From your computer.AssetUploadField')
                    ->getItemHandler($document->ID)->EditLink(),
                'size' => $document->getFileSizeFormatted(),
                'buttons' => $buttonText,
                'showeditform' => true
            );
        }

        return json_encode($return);
    }

    /**
     * Returns HTML representing a list of documents that are associated with the given page ID, across all document
     * sets.
     *
     * @return string HTML
     */
    public function documentlist()
    {
        if (!$this->getRequest()->getVar('pageID')) {
            return $this->httpError(400);
        }

        $page = SiteTree::get()->byId($this->getRequest()->getVar('pageID'));

        if ($page && $page->getAllDocuments()->count() > 0) {
            $list = '<ul>';

            foreach ($page->getAllDocuments() as $document) {
                $list .= sprintf(
                    '<li><a class="add-document" data-document-id="%s">%s</a></li>',
                    $document->ID,
                    $document->ID . ' - ' . Convert::raw2xml($document->Title)
                );
            }

            $list .= '</ul>';

            return $list;
        }

        return sprintf(
            '<p>%s</p>',
            _t('DMSDocumentAddController.NODOCUMENTS', 'There are no documents attached to the selected page.')
        );
    }

    /**
     * Get an array of allowed file upload extensions, merged with {@link File} and extra configuration from this
     * class
     *
     * @return array
     */
    public function getAllowedExtensions()
    {
        return array_filter(
            array_merge(
                (array) Config::inst()->get(File::class, 'allowed_extensions'),
                (array) $this->config()->get('allowed_extensions')
            )
        );
    }

    /**
     * Overrides the parent method to allow users with access to DMS admin to access this controller
     *
     * @param Member $member
     * @return bool
     */
    public function canView($member = null, $context = [])
    {
        if (!$member || !(is_a($member, Member::class)) || is_numeric($member)) {
            $member = Security::getCurrentUser();
        }

        if (
            $member &&
            Permission::checkMember(
                $member,
                array(
                    'CMS_ACCESS_DMSDocumentAdmin',
                )
            )
        ) {
            return true;
        }
        return parent::canView($member);
    }
}
