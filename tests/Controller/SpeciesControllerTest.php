<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SpeciesControllerTest extends WebTestCase
{
    /**
     * Test list species page.
     */
    public function testAnonymousListSpeciesPageIsValid()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/especes');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert list species page is StatusCode 200'
        );
    }

    /**
     * Test single species page.
     */
    public function testAnonymousSingleSpeciesPageIsValid()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/espece/abricotier');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert single species page is StatusCode 200'
        );
    }
}
