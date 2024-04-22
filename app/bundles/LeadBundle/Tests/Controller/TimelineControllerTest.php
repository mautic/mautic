<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\HttpFoundation\Response;

final class TimelineControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testIndexActionsIsSuccessful(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->flush();

        $crawler = $this->client->request('GET', '/s/contacts/timeline/'.$contact->getId());
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testFilterCaseInsensitive(): void
    {
        $contact = $this->createLead('TestFirstName');
        $segment = $this->createSegment('TEST', []);
        $this->createListLead($segment, $contact);
        $this->em->flush();
        $this->createLeadEventLogEntry($contact, 'lead', 'segment', 'added', $segment->getId(), [
            'object_description' => $segment->getName(),
        ]);
        $this->em->flush();

        $crawler = $this->client->request('POST', '/s/contacts/timeline/'.$contact->getId(), [
            'search' => 'test',
            'leadId' => $contact->getId(),
        ]);

        $this->assertStringContainsString('Contact added to segment, TEST', $this->client->getResponse()->getContent());
    }
}
