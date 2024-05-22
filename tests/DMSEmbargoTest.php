<?php

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\SapphireTest;

class DMSEmbargoTest extends SapphireTest
{
    protected static $fixture_file = 'dmsembargotest.yml';

    public function createFakeHTTPRequest($id)
    {
        $r = new HTTPRequest('GET', 'index/'.$id);
        $r->match('index/$ID');
        return $r;
    }

}
