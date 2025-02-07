<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class TimelineControllerTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;
    private const SALES_USER = 'sales';

    public function testIndexActionsIsSuccessful(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->flush();

        $this->client->request('GET', '/s/contacts/timeline/'.$contact->getId());
        $this->assertResponseIsSuccessful();
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

        $this->client->request('POST', '/s/contacts/timeline/'.$contact->getId(), [
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
    }

    public function testBatchExportActionAsUserNotPermission(): void
    {
        $contact = $this->createLead('TestFirstName');
        $this->em->persist($contact);
        $this->em->flush();

        $user = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        $this->loginUser($user);
        $this->client->request('GET', '/s/contacts/timeline/batchExport/'.$contact->getId());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }
}
