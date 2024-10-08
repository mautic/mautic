<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\HttpFoundation\Response;

final class TimelineControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    private const SALES_USER = 'sales';

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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testBatchExportActionAsAdmin(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->persist($contact);
        $this->em->flush();

        $this->client->request('GET', '/s/contacts/timeline/batchExport/'.$contact->getId());
        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBatchExportActionAsUserNotPermission(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->persist($contact);
        $this->em->flush();

        $this->loginUser(self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->request('GET', '/s/contacts/timeline/batchExport/'.$contact->getId());

        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }
}
