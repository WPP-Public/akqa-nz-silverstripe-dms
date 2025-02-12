<?php

namespace Sunnysideup\DMS\Tests;

use Sunnysideup\DMS\Model\DMSDocument;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Sunnysideup\DMS\DMS;
use SilverStripe\Dev\SapphireTest;

class DMSDocumentTest extends SapphireTest
{
    protected static $fixture_file = 'dmstest.yml';

    /**
     * Ensure that related documents can be retrieved for a given DMS document
     */
    public function testRelatedDocuments()
    {
        $document = $this->objFromFixture(DMSDocument::class, 'document_with_relations');
        $this->assertGreaterThan(0, $document->RelatedDocuments()->count());
        $this->assertEquals(
            ['test-file-file-doesnt-exist-1', 'test-file-file-doesnt-exist-2'],
            $document->getRelatedDocuments()->column('Name')
        );
    }

    /**
     * Test the extensibility of getRelatedDocuments
     */
    public function testGetRelatedDocumentsIsExtensible()
    {
        DMSDocument::add_extension('StubRelatedDocumentExtension');

        $document = DMSDocument::create();
        $relatedDocuments = $document->getRelatedDocuments();

        $this->assertCount(1, $relatedDocuments);
        $this->assertSame('Extended', $relatedDocuments->first()->Name);
    }

    /**
     * Ensure that HTML is returned containing list items with action panel steps
     */
    public function testGetActionTaskHtml()
    {
        $document = $this->objFromFixture(DMSDocument::class, 'd1');
        $document->addActionPanelTask('example', 'Example');

        $result = $document->getActionTaskHtml();

        $this->assertContains('<label class="left">Actions</label>', $result);
        $this->assertContains('<li class="ss-ui-button dmsdocument-action" data-panel="', $result);
        $this->assertContains('permission', $result);
        $this->assertContains('Example', $result);

        $actions = ['example', 'embargo', 'find-usage'];
        foreach ($actions as $action) {
            // Test remove with string
            $document->removeActionPanelTask($action);
        }
        $result = $document->getActionTaskHtml();

        $this->assertNotContains('Example', $result);
        $this->assertNotContains('embargo', $result);
        $this->assertNotContains('find-usage', $result);
        // Positive test to see some action still remains
        $this->assertContains('find-references', $result);
    }

    /*
     * Tests whether the permissions fields are added
     */
    public function testGetPermissionsActionPanel()
    {
        $result = $this->objFromFixture(DMSDocument::class, 'd1')->getPermissionsActionPanel();

        $this->assertInstanceOf(CompositeField::class, $result);
        $this->assertNotNull($result->getChildren()->fieldByName('CanViewType'));
        $this->assertNotNull($result->getChildren()->fieldByName('ViewerGroups'));
    }

    /**
     * Test view permissions
     */
    public function testCanView()
    {
        /** @var DMSDocument $document */
        $document = $this->objFromFixture(DMSDocument::class, 'doc-logged-in-users');
        $this->logoutMember();
        // Logged out user test
        $this->assertFalse($document->canView());

        // Logged in user test
        $adminID = $this->logInWithPermission();
        $admin = Member::get()->byID($adminID);
        $this->assertTrue($document->canView($admin));
        /** @var Member $member */
        $admin->logout();

        // Check anyone
        $document = $this->objFromFixture(DMSDocument::class, 'doc-anyone');
        $this->assertTrue($document->canView());

        // Check OnlyTheseUsers
        $document = $this->objFromFixture(DMSDocument::class, 'doc-only-these-users');
        $reportAdminID = $this->logInWithPermission('cable-guy');
        /** @var Member $reportAdmin */
        $reportAdmin = Member::get()->byID($reportAdminID);
        $this->assertFalse($document->canView($reportAdmin));
        // Add reportAdmin to group
        $reportAdmin->addToGroupByCode('content-author');
        $this->assertTrue($document->canView($reportAdmin));
        $reportAdmin->logout();
    }

    /**
     * Tests edit permissions
     */
    public function testCanEdit()
    {
        $this->logoutMember();

        /** @var DMSDocument $document1 */
        $document1 = $this->objFromFixture(DMSDocument::class, 'doc-logged-in-users');

        // Logged out user test
        $this->assertFalse($document1->canEdit());

        //Logged in user test
        $contentAuthor = $this->objFromFixture(Member::class, 'editor');
        $this->assertTrue($document1->canEdit($contentAuthor));

        // Check OnlyTheseUsers
        /** @var DMSDocument $document2 */
        $document2 = $this->objFromFixture(DMSDocument::class, 'doc-only-these-users');
        /** @var Member $cableGuy */
        $cableGuy = $this->objFromFixture(Member::class, 'non-editor');
        $this->assertFalse($document2->canEdit($cableGuy));

        $cableGuy->addToGroupByCode('content-author');
        $this->assertTrue($document2->canEdit($cableGuy));
    }

    /**
     * Tests delete permissions
     */
    public function testCanDelete()
    {
        $this->logoutMember();
        $document1 = $this->objFromFixture(DMSDocument::class, 'doc-logged-in-users');

        // Logged out user test
        $this->assertFalse($document1->canDelete());

        // Test editors can delete
        $contentAuthor = $this->objFromFixture(Member::class, 'editor');
        $this->assertTrue($document1->canDelete($contentAuthor));
    }

    /**
     * Tests create permission
     */
    public function testCanCreate()
    {
        $this->logoutMember();
        $document1 = $this->objFromFixture(DMSDocument::class, 'doc-logged-in-users');
        $this->logInWithPermission('CMS_ACCESS_DMSDocumentAdmin');
        // Test CMS access can create
        $this->assertTrue($document1->canCreate());

        $this->logoutMember();

        // Test editors can create
        $contentAuthor = $this->objFromFixture(Member::class, 'editor');
        $this->assertTrue($document1->canCreate($contentAuthor));
    }

    /**
     * Logs out any active member
     */
    protected function logoutMember()
    {
        if ($member = Security::getCurrentUser()) {
            $member->logOut();
        }
    }

    /**
     * Test permission denied reasons for documents
     */
    public function testGetPermissionDeniedReason()
    {
        /** @var DMSDocument $document1 */
        $doc1 = $this->objFromFixture(DMSDocument::class, 'doc-logged-in-users');
        $this->assertContains('Please log in to view this document', $doc1->getPermissionDeniedReason());

        /** @var DMSDocument $doc2 */
        $doc2 = $this->objFromFixture(DMSDocument::class, 'doc-only-these-users');
        $this->assertContains('You are not authorised to view this document', $doc2->getPermissionDeniedReason());

        /** @var DMSDocument $doc3 */
        $doc3 = $this->objFromFixture(DMSDocument::class, 'doc-anyone');
        $this->assertEquals('', $doc3->getPermissionDeniedReason());
    }

    /**
     * Ensure that all pages that a document belongs to (via many document sets) can be retrieved in one list
     */
    public function testGetRelatedPages()
    {
        $document = $this->objFromFixture(DMSDocument::class, 'd1');
        $result = $document->getRelatedPages();
        $this->assertCount(3, $result, 'Document 1 is related to 3 Pages');
        $this->assertSame(['s1', 's2', 's3'], $result->column('URLSegment'));
    }

    /**
     * Test that the title is returned if it is set, otherwise the filename without ID
     */
    public function testGetTitleOrFilenameWithoutId()
    {
        $d1 = $this->objFromFixture(DMSDocument::class, 'd1');
        $this->assertSame('test-file-file-doesnt-exist 1', $d1->getTitle());

        $d2 = $this->objFromFixture(DMSDocument::class, 'd2');
        $this->assertSame('File That Doesn\'t Exist (Title)', $d2->getTitle());
    }

    /**
     * Ensure that the folder a document's file is stored in can be retrieved, and that delete() will also delete
     * the file and the record
     */
    public function testGetStorageFolderThenDelete()
    {
        Config::modify()->update(DMS::class, 'folder_name', 'assets/_unit-tests');

        $document = DMS::inst()->storeDocument('dms/tests/DMS-test-lorum-file.pdf');
        $filename = $document->getStorageFolder() . '/' . $document->getFilename();

        $this->assertTrue(file_exists($filename));
        $document->delete();
        $this->assertFalse($document->exists());
        $this->assertFalse(file_exists($filename));

        DMSFilesystemTestHelper::delete('assets/_unit-tests');
    }

    /**
     * Test that the link contains an ID and URL slug
     */
    public function testGetLink()
    {
        Config::modify()->update(DMS::class, 'folder_name', 'assets/_unit-tests');

        $document = DMS::inst()->storeDocument('dms/tests/DMS-test-lorum-file.pdf');

        $expected = '/dmsdocument/' . $document->ID . '-dms-test-lorum-file-pdf';
        $this->assertSame($expected, $document->Link());
        $this->assertSame($expected, $document->getLink());
    }

    /**
     * Ensure that the description can be returned in HTML format
     */
    public function testGetDescriptionWithLineBreak()
    {
        $document = DMSDocument::create();
        $document->Description = "Line 1\nLine 2\nLine 3";
        $document->write();

        $this->assertSame("Line 1<br />\nLine 2<br />\nLine 3", $document->getDescriptionWithLineBreak());
    }
