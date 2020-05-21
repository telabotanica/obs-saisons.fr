<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PagesControllerTest extends WebTestCase
{
    /**
     * Test pages.
     */
    public function testAnonymousAboutPageIsValid()
    {
        $client = static::createClient();

        $pages = [
            'apropos',
            'participer',
            'participer/protocole',
            'outils-ressources',
            'resultats',
            'relais',
        ];
        foreach ($pages as $page) {
            $crawler = $client->request('GET', '/'.$page);

            $this->assertEquals(
                200,
                $client->getResponse()->getStatusCode(),
                'Assert '.$page.' is StatusCode 200'
            );
        }

    }
}
