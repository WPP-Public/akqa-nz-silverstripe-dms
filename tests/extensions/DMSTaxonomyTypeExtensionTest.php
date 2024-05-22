<?php

namespace Sunnysideup\DMS\Tests\Extensions;

use Sunnysideup\DMS\Extensions\DMSTaxonomyTypeExtension;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Taxonomy\TaxonomyType;

class DMSTaxonomyTypeExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        'TaxonomyType' => [DMSTaxonomyTypeExtension::class]
    ];

    /**
     * Ensure that the configurable list of default records are created
     */
    public function testDefaultRecordsAreCreated()
    {
        Config::modify()->set(DMSTaxonomyTypeExtension::class, 'default_records', ['Food', 'Beverage', 'Books']);

        TaxonomyType::create()->requireDefaultRecords();

        $this->assertContains(
            [
                ['Name' => 'Food'],
                ['Name' => 'Beverage'],
                ['Name' => 'Books'],
            ],
            TaxonomyType::get()
        );
    }
}
