<?php

namespace Mautic\LeadBundle\Tests\Functional\Event;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;

class LeadListFilteringEventTest extends MauticMysqlTestCase
{
    public function testIconIfManuallyAdded(): void
    {
        $em           = self::getContainer()->get('doctrine')->getManager();
        $contactModel = self::getContainer()->get('mautic.lead.model.lead');
        $segmentModel = self::getContainer()->get('mautic.lead.model.list');

        // Create a segment
        $segment = new LeadList();
        $segment->setName('Test Segment');
        $segment->setPublicName('Test Segment');
        $segment->setAlias('test-segment');
        $segment->setFilters([]);
        $segmentModel->saveEntity($segment);
        $em->flush();

        // Test manually added contact (should NOT have filter icon)
        // Create first contact
        $contact1 = new Lead();
        $contact1->setFirstname('John');
        $contact1->setLastname('Doe');
        $contact1->setEmail('john.doe@test.com');
        $contactModel->saveEntity($contact1);
        $em->flush();

        // Manually add the contact to segment
        $segmentModel->addLead($contact1, $segment, true);
        $em->flush();

        // Process segment
        $segmentModel->rebuildListLeads($segment);
        $em->flush();

        // Check the contact detail page
        $crawler = $this->client->request('GET', '/s/contacts/view/'.$contact1->getId());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $segmentHeader = $crawler->filter('.panel-segments h5');
        $this->assertStringNotContainsString('ri-filter-2-line', $segmentHeader->html(),
            'Filter icon should NOT be present when contacts are manually added to segment');

        // Test dynamically filtered contact
        $contact2 = new Lead();
        $contact2->setFirstname('Jane');
        $contact2->setLastname('Smith');
        $contact2->setEmail('jane.smith@test.com');
        $contactModel->saveEntity($contact2);
        $em->flush();

        // Configure segment with filter to match our second contact dynamically
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'filter'   => 'jane.smith@test.com',
                'display'  => null,
                'operator' => '=',
            ],
        ]);
        $segmentModel->saveEntity($segment);
        $em->flush();

        // Rebuild the segment to add contact dynamically
        $segmentModel->rebuildListLeads($segment);
        $em->flush();

        // Check the contact detail page for the second contact
        $crawler = $this->client->request('GET', '/s/contacts/view/'.$contact2->getId());
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $segmentHeader = $crawler->filter('.panel-segments h5');
        $this->assertStringContainsString('ri-filter-2-line', $segmentHeader->html(),
            'Filter icon should be present when contacts are added dynamically through filters');
    }
}
