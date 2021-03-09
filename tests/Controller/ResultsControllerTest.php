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

        $crawler = $client->request('GET', '/explorer-les-donnees');

        $this->assertGreaterThan(
            0,
            $crawler->filter('#results-map')->count(),
            'Assert page contains a map info'
        );
    }
}
