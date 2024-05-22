<?php

namespace Sunnysideup\DMS\Tests\Extensions;

use Sunnysideup\DMS\Extensions\DMSDocumentTaxonomyExtension;
use SilverStripe\Dev\SapphireTest;

class DMSDocumentTaxonomyExtensionTest extends SapphireTest
{
    protected static $fixture_file = 'DMSDocumentTaxonomyExtensionTest.yml';

    /**
     * Ensure that appropriate tags by taxonomy type are returned, and that their hierarchy is displayd in the title
     */
    public function testGetAllTagsMap()
    {
        $extension = new DMSDocumentTaxonomyExtension();
        $result = $extension->getAllTagsMap();

        $this->assertContains('Subject > Mathematics', $result);
        $this->assertContains('Subject', $result);
        $this->assertContains('Subject > Science > Chemistry', $result);
        $this->assertNotContains('Physical Education', $result);
    }
}
