<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FrontControllerTest extends WebTestCase
{
    /**
     * Test homepage.
     */
    public function testAnonymousHomepageIsValid()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert homepage is StatusCode 200'
        );

        $this->assertGreaterThan(
            0,
            $crawler->filter('a.nav-item.resp-connect')->count(),
            'Assert header contains a Connexion link'
        );
    }
}
