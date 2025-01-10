<?php

namespace Sunnysideup\DMS\Tests;

use Sunnysideup\DMS\Model\DMSDocumentSet;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Dev\SapphireTest;

class DMSDocumentSetTest extends SapphireTest
{
    protected static $fixture_file = 'dmstest.yml';

    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Ensure that getDocuments is extensible
     */
    public function testGetDocumentsIsExtensible()
    {
        DMSDocumentSet::add_extension('StubRelatedDocumentExtension');

        $set = DMSDocumentSet::create();
        $documents = $set->getDocuments();

        $this->assertCount(1, $documents);
        $this->assertSame('Extended', $documents->first()->Name);
    }

    /**
     * Test that the GridField for documents isn't shown until you've saved the set
     */
    public function testGridFieldShowsWhenSetIsSaved()
    {
        $set = DMSDocumentSet::create();

        // Not in database yet
        $fields = $set->getCMSFields();
        $this->assertNull($fields->fieldByName('Documents'));

        // In the database
        $set->Title = 'Testing';
        $set->write();
        $fields = $set->getCMSFields();
        $gridField = $fields->dataFieldByName('Documents');
        $this->assertNotNull($gridField);
    }

    public function testRelations()
    {
        $s1 = $this->objFromFixture(SiteTree::class, 's1');
        $s2 = $this->objFromFixture(SiteTree::class, 's2');
        $s4 = $this->objFromFixture(SiteTree::class, 's4');

        $ds1 = $this->objFromFixture(DMSDocumentSet::class, 'ds1');
        $ds2 = $this->objFromFixture(DMSDocumentSet::class, 'ds2');
        $ds3 = $this->objFromFixture(DMSDocumentSet::class, 'ds3');

        $this->assertCount(0, $s4->DocumentSets(), 'Page 4 has no document sets associated');
        $this->assertCount(2, $s1->DocumentSets(), 'Page 1 has 2 document sets');
        $this->assertEquals([$ds1->ID, $ds2->ID], $s1->DocumentSets()->column('ID'));
    }

    /**
     * Ensure that the display fields for the documents GridField can be returned
     */
    public function testGetDocumentDisplayFields()
    {
        $document = $this->objFromFixture(DMSDocumentSet::class, 'ds1');
        $this->assertIsArray($document->getDocumentDisplayFields());

        Config::modify()->set(DMSDocument::class, 'display_fields', ['apple' => 'Apple', 'orange' => 'Orange']);
        $displayFields = $document->getDocumentDisplayFields();
        $this->assertContains('Apple', $displayFields);
        $this->assertContains('Orange', $displayFields);
        $this->assertContains('Added', $displayFields);
    }

    /**
     * Ensure that when editing in a page context that the "page" field is removed, or is labelled "Show on page"
     * otherwise
     */
    public function testPageFieldRemovedWhenEditingInPageContext()
    {
        $set = $this->objFromFixture(DMSDocumentSet::class, 'ds1');

        $fields = $set->getCMSFields();
        $this->assertInstanceOf(DropdownField::class, $fields->dataFieldByName('PageID'));
    }

    /**
     * Tests all crud permissions
     */
    public function testPermissions()
    {
        $this->logOut();

        $set = $this->objFromFixture(DMSDocumentSet::class, 'ds1');

        $this->assertFalse($set->canCreate());
        $this->assertFalse($set->canDelete());
        $this->assertFalse($set->canEdit());
        $this->assertFalse($set->canView());

        $this->logInWithPermission('CMS_ACCESS_DMSDocumentAdmin');
        $this->assertTrue($set->canCreate());
        $this->assertTrue($set->canDelete());
        $this->assertTrue($set->canEdit());
        $this->assertTrue($set->canView());
    }
}
