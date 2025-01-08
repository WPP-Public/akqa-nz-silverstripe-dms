<?php

namespace Sunnysideup\DMS;

use SilverStripe\Core\Config\Configurable;

class DMS
{
    use Configurable;

    /**
     * @var string
     * @config
     */
    private static string $folder_name = 'dms';

    /**
     * @var string
     * @config
     */
    private static string $shortcode_handler_key = 'dms';

    /**
     * Get the shortcode handler key
     */
    public static function getShortcodeHandlerKey(): string
    {
        return self::config()->get('shortcode_handler_key');
    }

    /**
     * Get the folder name
     */
    public static function getFolderName(): string
    {
        return self::config()->get('folder_name');
    }
}