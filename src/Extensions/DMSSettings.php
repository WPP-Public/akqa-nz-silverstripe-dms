<?php

namespace Sunnysideup\DMS\Extensions;

use SilverStripe\Assets\Folder;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;

/**
 * Settings class to define site specific settings.
 */

class DMSSettings extends Extension
{
    private static $has_one = [
        'DMSFolder' => Folder::class
    ];

    /**
     * Returns the fields that should be rendered in the admin module.
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab(
            'Root.DMS',
            DropdownField::create(
                'DMSFolderID',
                'DMS Documents Folder',
                Folder::get()->filter(['ParentID' => 0])
            )
                ->setEmptyString('--- Please select a folder --- ')
                ->setRightTitle('All DMS documents will be stored in either this folder or a child folder of this folder')
        );
        return $fields;
    }
}
