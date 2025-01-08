<?php

namespace Sunnysideup\DMS\Extensions;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Taxonomy\TaxonomyType;

/**
 * Creates default taxonomy type records if they don't exist already
 */

class DMSTaxonomyTypeExtension extends Extension
{
    public function requireDefaultRecords()
    {
        $records = (array) Config::inst()->get(get_class($this), 'default_records');
        foreach ($records as $name) {
            $type = TaxonomyType::get()->filter('Name', $name)->first();

            if (!$type) {
                $type = TaxonomyType::create(['Name' => $name]);
                $type->write();
            }
        }
    }
}
