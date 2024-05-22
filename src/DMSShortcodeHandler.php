<?php

namespace Sunnysideup\DMS;

use SilverStripe\View\Parsers\ShortcodeParser;
use Sunnysideup\DMS\Model\DMSDocument;

class DMSShortcodeHandler
{
    public static function handle($arguments, $content, ShortcodeParser $parser, $tag, array $extra = [])
    {
        if (!empty($arguments['id'])) {
            $document = DMSDocument::get()->byID($arguments['id']);

            if ($document && !$document->isHidden()) {
                if ($content) {
                    return sprintf(
                        '<a href="%s">%s</a>',
                        $document->Link(),
                        $parser->parse($content)
                    );
                }

                if (isset($extra['element'])) {
                    $extra['element']->setAttribute('data-ext', $document->getExtension());
                    $extra['element']->setAttribute('data-size', $document->getFileSizeFormatted());
                }

                return $document->Link();
            }
        }

        return '';
    }
}
