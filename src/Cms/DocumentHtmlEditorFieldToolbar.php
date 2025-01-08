<?php

namespace YourNamespace\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\HiddenField;
use Sunnysideup\DMS\Cms\DMSDocumentAddExistingField;
use Sunnysideup\DMS\DMS;

class DocumentHTMLEditorFieldToolbar extends Extension
{
    /**
     * Update the HTML editor link form to include document linking functionality
     *
     * @param Form $form
     * @return void
     */
    public function updateLinkForm(Form $form): void
    {
        $linkType = null;
        $fieldList = null;
        $fields = $form->Fields();

        // Find the LinkType field
        foreach ($fields as $field) {
            if ($linkTypeField = $field->fieldByName('LinkType')) {
                $linkType = $linkTypeField;
                $fieldList = $field;
                break;
            }
        }

        // If we couldn't find the required fields, return early
        if (!$linkType || !$fieldList) {
            return;
        }

        // Add document option to link types
        $source = $linkType->getSource();
        $source['document'] = _t(
            __CLASS__ . '.DOWNLOAD_DOCUMENT',
            'Download a document'
        );
        $linkType->setSource($source);

        // Create and configure the add existing field
        $addExistingField = DMSDocumentAddExistingField::create(
            'AddExisting',
            _t(__CLASS__ . '.ADD_EXISTING', 'Add Existing')
        );
        $addExistingField->setForm($form)
            ->setUseFieldClass(false);

        // Add the field after the Description field
        $fieldList->insertAfter('Description', $addExistingField);

        // Add the shortcode handler key
        $fieldList->push(
            HiddenField::create(
                'DMSShortcodeHandlerKey',
                false,
                DMS::getShortcodeHandlerKey()
            )
        );
    }
}
