<?php

namespace Mechanize\Tests;

use Mechanize\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    public function testHeaders()
    {
        $client = new Client;

        $client->addHeaders(array(
            'X-Header-Added' => 'Yes',
        ));

        $headers = $client->getHeaders();

        $this->assertTrue(isset($headers['X-Header-Added']));
        $this->assertEquals('Yes', $headers['X-Header-Added']);

        $client->resetHeaders();

        $this->assertEmpty($client->getHeaders());
    }

    public function testAbsoluteUrlConversions()
    {
        $client = new Client;
        $client->get('http://example.com/section/area/example.html');

        $this->assertEquals('http://example.com/section/area', $client->absoluteUrl('../'));
        $this->assertEquals('http://example.com/section', $client->absoluteUrl('../../'));

        $this->assertEquals('http://example.com/images/asset.jpg', $client->absoluteUrl('//images/asset.jpg'));
        $this->assertEquals('http://example.com/images/asset.jpg', $client->absoluteUrl('./images/asset.jpg'));
    }

    public function testAbsoluteUrlConversionsUsingHttps()
    {
        $client = new Client;
        $client->get('https://example.com/section/area/example.html');

        $this->assertEquals('https://example.com/section/area', $client->absoluteUrl('../'));
        $this->assertEquals('https://example.com/section', $client->absoluteUrl('../../'));

        $this->assertEquals('https://example.com/images/asset.jpg', $client->absoluteUrl('//images/asset.jpg'));
        $this->assertEquals('https://example.com/images/asset.jpg', $client->absoluteUrl('./images/asset.jpg'));
    }

    public function testResetAfterRequest()
    {
        $client = new Client;
        $client->setHeaders(array(
            'X-Header-Added' => 'Yes'
        ));
        $client->get('http://example.com');

        $this->assertEmpty($client->getHeaders());
    }
}