<?php

namespace Sunnysideup\DMS\Tests;

use Sunnysideup\DMS\Model\DMSDocumentSet;
use SilverStripe\CMS\Model\SiteTree;
use Sunnysideup\DMS\Cms\DMSGridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Core\Config\Config;
use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\FieldGroup;
use SilverStripe\Forms\DropdownField;
use Sunnysideup\DMS\DMS;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\CMS\Controllers\CMSPageEditController;
use SilverStripe\Dev\SapphireTest;

class DMSDocumentSetTest extends SapphireTest
{
    protected static $fixture_file = 'dmstest.yml';

    /**
     * Ensure that getDocuments is extensible
     */
    public function testGetDocumentsIsExtensible()
    {
        DMSDocumentSet::add_extension('StubRelatedDocumentExtension');

        $set = new DMSDocumentSet;
        $documents = $set->getDocuments();

        $this->assertCount(1, $documents);
        $this->assertSame('Extended', $documents->first()->Filename);
    }

    /**
     * Test that the GridField for documents isn't shown until you've saved the set
     */
    public function testGridFieldShowsWhenSetIsSaved()
    {
        $set = DMSDocumentSet::create();

        // Not in database yet
        $fields = $set->getCMSFields();
        $this->assertNull($fields->fieldByName('Root.Main.Documents'));
        $gridFieldNotice = $fields->fieldByName('Root.Main.GridFieldNotice');
        $this->assertNotNull($gridFieldNotice);
        $this->assertContains('Managing documents will be available', $gridFieldNotice->getContent());

        // In the database
        $set->Title = 'Testing';
        $set->write();
        $fields = $set->getCMSFields();
        $this->assertNotNull($fields->fieldByName('Root.Main.Documents'));
        $this->assertNull($fields->fieldByName('Root.Main.GridFieldNotice'));
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
     * Test that various components exist in the GridField config. See {@link DMSDocumentSet::getCMSFields} for context.
     */
    public function testDocumentGridFieldConfig()
    {
        $set = $this->objFromFixture(DMSDocumentSet::class, 'ds1');
        $fields = $set->getCMSFields();
        $gridField = $fields->fieldByName('Root.Main.Documents');
        $this->assertTrue((bool) $gridField->hasClass('documents'));

        /** @var GridFieldConfig $config */
        $config = $gridField->getConfig();

        $this->assertNotNull($addNew = $config->getComponentByType(DMSGridFieldAddNewButton::class));
        $this->assertSame($set->ID, $addNew->getDocumentSetId());

        if (class_exists('GridFieldPaginatorWithShowAll')) {
            $this->assertNotNull($config->getComponentByType('GridFieldPaginatorWithShowAll'));
        } else {
            $paginator = $config->getComponentByType(GridFieldPaginator::class);
            $this->assertNotNull($paginator);
            $this->assertSame(15, $paginator->getItemsPerPage());
        }

        $sortableAssertion = class_exists('GridFieldSortableRows') ? 'assertNotNull' : 'assertNull';
        $this->$sortableAssertion($config->getComponentByType('GridFieldSortableRows'));
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
        $this->assertArrayHasKey('ManuallyAdded', $displayFields);
        $this->assertContains('Added', $displayFields);
    }

    /**
     * Tests to ensure that the callback for formatting ManuallyAdded will return a nice label for the user
     */
    public function testNiceFormattingForManuallyAddedInGridField()
    {
        $fieldFormatting = $this->objFromFixture(DMSDocumentSet::class, 'ds1')
            ->getCMSFields()
            ->fieldByName('Root.Main.Documents')
            ->getConfig()
            ->getComponentByType(GridFieldDataColumns::class)
            ->getFieldFormatting();

        $this->assertArrayHasKey('ManuallyAdded', $fieldFormatting);
        $this->assertTrue(is_callable($fieldFormatting['ManuallyAdded']));

        $this->assertSame('Manually', $fieldFormatting['ManuallyAdded'](1));
        $this->assertSame('Query Builder', $fieldFormatting['ManuallyAdded'](0));
    }

    /**
     * Ensure that the "direction" dropdown field has user friendly field labels
     */
    public function testQueryBuilderDirectionFieldHasFriendlyLabels()
    {
        $fields = $this->objFromFixture(DMSDocumentSet::class, 'ds1')->getCMSFields();

        $dropdown = $fields->fieldByName('Root.QueryBuilder')->FieldList()->filterByCallback(function ($field) {
            return $field instanceof FieldGroup;
        })->first()->fieldByName('SortByDirection');

        $this->assertInstanceOf(DropdownField::class, $dropdown);
        $source = $dropdown->getSource();
        $this->assertContains('Ascending', $source);
        $this->assertContains('Descending', $source);
    }

    /**
     * Ensure that the configurable shortcode handler key is a hidden field in the CMS
     */
    public function testShortcodeHandlerKeyFieldExists()
    {
        Config::modify()->set(DMS::class, 'shortcode_handler_key', 'unit-test');

        $set = DMSDocumentSet::create(['Title' => 'TestSet']);
        $set->write();

        $fields = $set->getCMSFields();
        $field = $fields->fieldByName('Root.Main.DMSShortcodeHandlerKey');

        $this->assertInstanceOf(HiddenField::class, $field);
        $this->assertSame('unit-test', $field->Value());
    }

    /**
     * Ensure that if the module is available, the orderable rows GridField component is added
     */
    public function testDocumentsAreOrderable()
    {
        if (!class_exists('GridFieldSortableRows')) {
            $this->markTestSkipped('Test requires undefinedoffset/sortablegridfield installed.');
        }

        $fields = $this->objFromFixture(DMSDocumentSet::class, 'ds1')->getCMSFields();

        $gridField = $fields->fieldByName('Root.Main.Documents');
        $this->assertInstanceOf(GridField::class, $gridField);

        $this->assertInstanceOf(
            'GridFieldSortableRows',
            $gridField->getConfig()->getComponentByType('GridFieldSortableRows')
        );
    }

    /**
     * Tests that an exception is thrown if no title entered for a DMSDocumentSet.
     *
     * @expectedException \SilverStripe\ORM\ValidationException
     */
    public function testExceptionOnNoTitleGiven()
    {
        DMSDocumentSet::create(['Title' => ''])->write();
    }

    /**
     * Ensure that when editing in a page context that the "page" field is removed, or is labelled "Show on page"
     * otherwise
     */
    public function testPageFieldRemovedWhenEditingInPageContext()
    {
        $set = $this->objFromFixture(DMSDocumentSet::class, 'ds1');

        $fields = $set->getCMSFields();
        $this->assertInstanceOf(DropdownField::class, $fields->fieldByName('Root.Main.PageID'));

        $pageController = new CMSPageEditController;
        $pageController->pushCurrent();

        $fields = $set->getCMSFields();
        $this->assertNull($fields->fieldByName('Root.Main.PageID'));
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
