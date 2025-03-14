<?php

namespace Mautic\LeadBundle\Tests\Functional\Event;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

class LeadListFilteringEventTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->resetAutoincrement([
            'leads',
            'lead_lists',
            'lead_lists_leads',
        ]);
    }

    public function testIconIfManuallyAdded(): void
    {
        // Create a contact
        $contact = new Lead();
        $contact->setFirstname('John');
        $contact->setLastname('Doe');
        $contact->setEmail('john.doe@test.com');

        $contactModel = self::getContainer()->get('mautic.lead.model.lead');
        $contactModel->saveEntity($contact);

        // Create a segment
        $segment = new LeadList();
        $segment->setName('Test Segment');
        $segment->setPublicName('Test Segment');
        $segment->setAlias('test-segment');

        // No filters so no contacts should be automatically added
        $segment->setFilters([]);

        $segmentModel = self::getContainer()->get('mautic.lead.model.list');

        // Manually add the contact to segment
        $segmentModel->addLead($contact, $segment);

        $segmentModel->saveEntity($segment);

        // Process segment
        $segmentModel->rebuildListLeads($segment);

        // Check the segment detail page to verify the filter icon is present
        $crawler = $this->client->request('GET', '/s/segments/view/'.$segment->getId());

        // Ensure request was successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check for filter icon
        $segmentHeader = $crawler->filter('h3.panel-title');
        $this->assertStringContainsString('fa-filter', $segmentHeader->html(), 'Filter icon should be present when contacts are manually added to segment');

        // Now remove all manually added contacts
        $segmentModel->removeLead($contact, $segment, true);

        // Check the segment detail page again
        $crawler = $this->client->request('GET', '/s/segments/view/'.$segment->getId());

        // Ensure request was successful
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        // Check that filter icon is no longer present
        $segmentHeader = $crawler->filter('h3.panel-title');
        $this->assertStringNotContainsString('fa-filter', $segmentHeader->html(), 'Filter icon should not be present when no contacts are manually added');
    }
}
