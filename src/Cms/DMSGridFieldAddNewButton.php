<?php

namespace Sunnysideup\DMS\Cms;

use Sunnysideup\DMS\Cms\DMSDocumentAddController;
use SilverStripe\Control\Controller;
use Sunnysideup\DMS\Model\DMSDocumentSet;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridField_HTMLProvider;

class DMSGridFieldAddNewButton extends GridFieldAddNewButton implements GridField_HTMLProvider
{
    /**
     * The document set ID that the document should be attached to
     *
     * @var int
     */
    protected $documentSetId;

    /**
     * Get the HTML fragments for the add button
     *
     * @param GridField $gridField
     */
    public function getHTMLFragments($gridField): array
    {
        $modelClass = $gridField->getModelClass();
        $singleton = $modelClass::singleton();

        if (!$singleton->canCreate()) {
            return [];
        }

        if (empty($this->buttonName)) {
            $objectName = $singleton->i18n_singular_name();
            $this->buttonName = _t(
                'GridField.Add',
                'Add {name}',
                ['name' => $objectName]
            );
        }

        $link = DMSDocumentAddController::singleton()->Link();

        // Add document set ID if available
        $documentSetId = $this->getDocumentSetId();
        if ($documentSetId) {
            $link = Controller::join_links($link, '?dsid=' . $documentSetId);

            // Look for an associated page, but only share it if we're editing in a page context
            $set = DMSDocumentSet::get()->byId($documentSetId);

            if (
                $set
                && $set->exists()
                && $set->Page()->exists()
                && Controller::curr() instanceof CMSPageEditController
            ) {
                $link = Controller::join_links($link, '?page_id=' . $set->Page()->ID);
            }
        }

        $data = ArrayData::create([
            'NewLink' => $link,
            'ButtonName' => $this->buttonName,
        ]);

        return [
            $this->targetFragment => $data->renderWith(self::class)
        ];
    }


    /**
     * Set the document set ID that this document should be attached to
     *
     * @param  int $id
     * @return $this
     */
    public function setDocumentSetId($id)
    {
        $this->documentSetId = $id;
        return $this;
    }

    /**
     * Get the document set ID that this document should be attached to
     *
     * @return int
     */
    public function getDocumentSetId()
    {
        return $this->documentSetId;
    }
}