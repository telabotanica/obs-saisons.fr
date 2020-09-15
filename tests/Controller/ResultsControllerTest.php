<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ResultsControllerTest extends WebTestCase
{
    /**
     * Test results page.
     */
    public function testResultsPageIsValid()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/resultats');

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode(),
            'Assert results page is StatusCode 200'
        );

        $this->assertGreaterThan(
            0,
            $crawler->filter('#results-map')->count(),
            'Assert page contains a map info'
        );
    }
}
