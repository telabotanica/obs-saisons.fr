<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ExportControllerTest extends WebTestCase
{
    /**
     * Test export.
     */
    public function testExportIsValid()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/export/observation/events-evolution');

        $this->assertEquals(
            400,
            $client->getResponse()->getStatusCode(),
            'Assert export obs events evolution without params is StatusCode 400'
        );

        $crawler = $client->request('GET', '/export/observation/events-evolution?species=3085&event=337,338');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export obs events evolution with params is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export/filtered');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export filtered without params is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export/filtered?species=3085&event=337,338');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export filtered with params is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export/species');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export species is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export/events');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export events is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export/retro');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export retro is StatusCode 200'
        );

        $crawler = $client->request('GET', '/export');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert export is StatusCode 200'
        );
    }
}
