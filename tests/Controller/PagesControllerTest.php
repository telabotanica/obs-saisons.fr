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
            '/actualites',
            '/evenements',
            '/especes',
            '/a-propos',
            '/aide',
            '/faq',
            '/glossaire',
            '/participer',
            '/resultats',
            '/resultats-scientifiques',
            '/lettres-de-printemps',
            '/explorer-les-donnees',
            '/outils-ressources',
            '/outils',
            '/ressources-pedagogiques',
            '/transmettre',
            '/relais',
            '/devenir-relais',
            '/se-former',
            '/les-relais-ods',
            '/ods-provence',
            '/mentions-legales',
        ];
        foreach ($pages as $page) {
            $crawler = $client->request('GET', $page);

            $this->assertEquals(
                200,
                $client->getResponse()->getStatusCode(),
                sprintf('Assert page %s is StatusCode 200', $page)
            );
        }
    }
}
