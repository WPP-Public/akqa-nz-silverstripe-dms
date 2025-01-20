<?php

namespace Sunnysideup\DMS;

use SilverStripe\View\Parsers\ShortcodeParser;
use Sunnysideup\DMS\Model\DMSDocument;

class DMSShortcodeHandler
{
    public static function handle(
        array $arguments,
        ?string $content,
        ShortcodeParser $parser,
        string $tag,
        array $extra = []
    ): string {
        if (empty($arguments['id'])) {
            return '';
        }

        $document = DMSDocument::get()->byId((int)$arguments['id']);

        if (!$document || $document->isHidden()) {
            return '';
        }

        // If we have content, wrap it in a link
        if ($content) {
            return sprintf(
                '<a href="%s">%s</a>',
                htmlspecialchars($document->Link(), ENT_QUOTES),
                $parser->parse($content)
            );
        }

        // Add extra attributes if element is provided
        if (isset($extra['element'])) {
            $extra['element']->setAttribute('data-ext', $document->getExtension());
            $extra['element']->setAttribute('data-size', $document->getFileSizeFormatted());
        }

        return $document->Link();
    }
}
