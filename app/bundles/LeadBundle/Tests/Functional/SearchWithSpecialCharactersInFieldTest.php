<?php

namespace Mautic\LeadBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

class SearchWithSpecialCharactersInFieldTest extends AbstractSearchTest
{
    public function testGlobalSearchContactWithSpecialCharacterInName(): void
    {
        // Create a contact with first name 'R&D'
        $this->createContact([
            'firstname' => 'R&D',
            'lastname'  => 'Contact',
            'email'     => 'randd@example.com',
            'company'   => 'TestCompany',
        ]);

        // URL-encode the ampersand: R&D becomes R%26D
        $response = $this->performSearch('/s/ajax?action=globalSearch&global_search=R%26D&tmp=list');
        $content  = \json_decode($response->getContent(), true);
        $crawler  = new Crawler($content['newContent'], $this->client->getInternalRequest()->getUri());

        // Assert that the contact name 'R&D' appears in the search results (html encoded)
        $this->assertStringContainsString('R&amp;D', $crawler->html());
    }
}
