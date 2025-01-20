<?php

namespace Sunnysideup\DMS\Extensions;

use SilverStripe\Core\Extension;

/**
 * Creates default taxonomy type records if they don't exist already
 */
class FileExtension extends Extension
{
    private static $db = [
        'OriginalDMSDocumentIDFile' => 'Int'
    ];

    private static $indexes = [
        'OriginalDMSDocumentIDFile' => true
    ];
}
