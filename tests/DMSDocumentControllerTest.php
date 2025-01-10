<?php

use SilverStripe\Core\Config\Config;
use Sunnysideup\DMS\DMS;
use Sunnysideup\DMS\Model\DMSDocumentController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;

/**
 * Class DMSDocumentControllerTest
 */
class DMSDocumentControllerTest extends SapphireTest
{
    protected static $fixture_file = 'dmstest.yml';

    /**
     * @var DMSDocumentController
     */
    protected $controller;

    public function setUp(): void
    {
        parent::setUp();

        Config::modify()->update(DMS::class, 'folder_name', 'assets/_unit-test-123');
        $this->logInWithPermission('ADMIN');

        $this->controller = $this->getMockBuilder(DMSDocumentController::class)
            ->setMethods(['sendFile'])
            ->getMock();
    }

    public function tearDown(): void
    {
        DMSFilesystemTestHelper::delete('assets/_unit-test-123');
        parent::tearDown();
    }

    /**
     * @return array[]
     */
    public function behaviourProvider()
    {
        return [
            ['open', 'inline'],
            ['download', 'attachment']
        ];
    }
}
