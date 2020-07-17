<?php

namespace App\Tests\Controller;

use App\Service\BreadcrumbsGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PagesControllerTest extends WebTestCase
{
    /**
     * Test pages.
     */
    public function testAnonymousAboutPageIsValid()
    {
        $client = static::createClient();

        $pages = array_keys(BreadcrumbsGenerator::MENU);
        foreach ($pages as $page) {
            $url = '/'.$page;
            $crawler = $client->request('GET', $url);

            $this->assertEquals(
                200,
                $client->getResponse()->getStatusCode(),
                sprintf('Assert page %s (%d) is StatusCode 200', $page, $url)
            );
        }

    }
}
