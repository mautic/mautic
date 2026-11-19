<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\EmailReply;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\VideoHit;

final class LeadModelEngagementsLeadIdsFunctionalTest extends MauticMysqlTestCase
{
    private const TYPES = ['page.hit', 'page.videohit', 'email.replied', 'lead.ipadded', 'lead.create', 'lead.identified'];

    public function testEngagementsAreRestrictedToLeadIds(): void
    {
        $included = $this->createContactWithActivity('included@example.com', '10.0.0.1');
        $this->createContactWithActivity('excluded@example.com', '10.0.0.2');
        $this->em->flush();

        $leadModel   = self::getContainer()->get(LeadModel::class);
        $engagements = $leadModel->getEngagements(
            null,
            ['search' => '', 'includeEvents' => self::TYPES, 'excludeEvents' => [], 'leadIds' => [$included->getId()]],
            null,
            1,
            25
        );

        $events = $engagements['events'];
        $this->assertSame(self::TYPES, array_values(array_intersect(self::TYPES, array_column($events, 'event'))));
        $this->assertCount(count(self::TYPES), $events);
        $this->assertSame(count(self::TYPES), (int) $engagements['total']);
        foreach ($events as $event) {
            $this->assertSame($included->getId(), (int) $event['contactId'], $event['event']);
        }
    }

    public function testActivityApiFiltersByLeadIds(): void
    {
        $included = $this->createContactWithActivity('included@example.com', '10.0.0.1');
        $this->createContactWithActivity('excluded@example.com', '10.0.0.2');
        $this->em->flush();

        $this->client->request('GET', '/api/contacts/activity', ['filters' => ['includeEvents' => self::TYPES, 'leadIds' => [$included->getId()]]]);
        self::assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $events   = $response['events'];
        $this->assertSame(self::TYPES, array_values(array_intersect(self::TYPES, array_column($events, 'event'))));
        $this->assertCount(count(self::TYPES), $events);
        $this->assertSame(count(self::TYPES), (int) $response['total']);
        foreach ($events as $event) {
            $this->assertSame($included->getId(), (int) $event['contactId'], $event['event']);
        }
    }

    private function createContactWithActivity(string $email, string $ip): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime('-1 day'));
        $this->em->persist($contact);
        $this->em->flush();

        $ipAddress = new IpAddress($ip);
        $this->em->persist($ipAddress);

        $hit = new Hit();
        $hit->setLead($contact);
        $hit->setIpAddress($ipAddress);
        $hit->setDateHit(new \DateTime('-1 hour'));
        $hit->setCode(200);
        $hit->setUrl('https://example.com');
        $hit->setTrackingId(uniqid());
        $this->em->persist($hit);

        $videoHit = new VideoHit();
        $videoHit->setLead($contact);
        $videoHit->setIpAddress($ipAddress);
        $videoHit->setDateHit(new \DateTime('-3 hours'));
        $videoHit->setCode(200);
        $videoHit->setUrl('https://example.com/video');
        $videoHit->setGuid(uniqid());
        $this->em->persist($videoHit);

        $stat = new Stat();
        $stat->setLead($contact);
        $stat->setEmailAddress($email);
        $stat->setDateSent(new \DateTime('-2 hours'));
        $stat->addReply(new EmailReply($stat, uniqid()));
        $this->em->persist($stat);

        $auditLog = new AuditLog();
        $auditLog->setBundle('lead');
        $auditLog->setObject('lead');
        $auditLog->setObjectId($contact->getId());
        $auditLog->setAction('ipadded');
        $auditLog->setIpAddress($ip);
        $auditLog->setUserId(0);
        $auditLog->setUserName('System');
        $auditLog->setDetails([]);
        $auditLog->setDateAdded(new \DateTime());
        $this->em->persist($auditLog);

        $staleAuditLog = clone $auditLog;
        $staleAuditLog->setIpAddress($ip.'0');
        $staleAuditLog->setDateAdded(new \DateTime('-1 year'));
        $this->em->persist($staleAuditLog);

        return $contact;
    }
}
