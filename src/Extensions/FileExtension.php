<?php

namespace Sunnysideup\DMS\Extensions;

use SilverStripe\ORM\DataExtension;

/**
 * Creates default taxonomy type records if they don't exist already
 */

class FileExtension extends DataExtension
{

    private static $db = [
        'OriginalDMSDocumentIDFile' => 'Int'
    ];

    private static $indexes = [
        'OriginalDMSDocumentIDFile' => true
    ];
}
