<?php

namespace Sunnysideup\DMS\Extensions;

use SilverStripe\Core\Extension;
use Sunnysideup\DMS\Model\DMSDocumentSet;
use SilverStripe\Forms\FieldList;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Security\Security;

class DMSSiteTreeExtension extends Extension
{
    private static $has_many = [
        'DocumentSets' => DMSDocumentSet::class
    ];

    private static $cascade_deletes = [
        'DocumentSets'
    ];

    private static $cascade_duplicates = [
        'DocumentSets'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        // Ability to disable document sets for a Page
        if (!$this->owner->config()->get('documents_enabled')) {
            return;
        }

        if (!Permission::checkMember(
            Security::getCurrentUser(),
            ['ADMIN', 'CMS_ACCESS_DMSDocumentAdmin']
        )) {
            return;
        }

        $gridField = GridField::create(
            'DocumentSets',
            false,
            $this->owner->DocumentSets(), //->Sort('DocumentSort'),
            $config = new GridFieldConfig_RelationEditor
        );
        $gridField->addExtraClass('documentsets');

        // Only show document sets in the autocompleter that have not been assigned to a page already
        $config->getComponentByType(GridFieldAddExistingAutocompleter::class)->setSearchList(
            DMSDocumentSet::get()->filter(['PageID' => 0])
        );

        $fields->addFieldToTab(
            'Root.DocumentSets',
            $gridField
        );

        $fields
            ->findOrMakeTab('Root.DocumentSets')
            ->setTitle(_t(
                __CLASS__ . '.DocumentSetsTabTitle',
                'Document Sets ({count})',
                ['count' => $this->owner->DocumentSets()->count()]
            ));
    }

    /**
     * Get a list of all documents from all document sets for the owner page
     *
     * @return ArrayList
     */
    public function getAllDocuments()
    {
        $documents = ArrayList::create();

        foreach ($this->owner->DocumentSets() as $documentSet) {
            /** @var DocumentSet $documentSet */
            $documents->merge($documentSet->getDocuments());
        }
        $documents->removeDuplicates();

        return $documents;
    }

    public function onBeforeDelete()
    {
        if ($this->owner->isOnDraft() || $this->owner->isPublished()) {
            //do nothing...
        } else {
            $dmsDocuments = $this->owner->getAllDocuments();

            foreach ($dmsDocuments as $document) {
                // If the document is only associated with one page, i.e. only associated with this page
                if ($document->getRelatedPages()->count() <= 1) {
                    $document->delete();
                }
            }
        }
    }

    /**
     * Returns the title of the page with the total number of documents it has associated with it across
     * all document sets
     *
     * @return string
     */
    public function getTitleWithNumberOfDocuments()
    {
        return $this->owner->Title . ' (' . $this->owner->getAllDocuments()->count() . ')';
    }
}
