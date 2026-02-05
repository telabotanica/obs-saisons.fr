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

            $statusCode = $client->getResponse()->getStatusCode();

            // Afficher les détails si erreur 500
            if ($statusCode === 500) {
                echo "\n=== ERROR ON PAGE: $page ===\n";
                echo $client->getResponse()->getContent();
                echo "\n=========================\n";

                // Optionnel : afficher les logs
                if ($client->getProfile()) {
                    $exception = $client->getProfile()->getCollector('exception');
                    if ($exception && $exception->hasException()) {
                        echo "\nException: " . $exception->getException()->getMessage() . "\n";
                        echo $exception->getException()->getTraceAsString() . "\n";
                    }
                }
            }

            $this->assertEquals(
                200,
                $statusCode,
                sprintf('Assert page %s is StatusCode 200', $page)
            );
        }
    }
}
