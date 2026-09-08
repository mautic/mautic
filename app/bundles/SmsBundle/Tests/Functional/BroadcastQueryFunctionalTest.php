<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Tests\Functional;

use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Entity\Sms;

final class BroadcastQueryFunctionalTest extends MauticMysqlTestCase
{
    use CreateEntitiesTrait;

    /**
     * The pending contacts query selects DISTINCT l.id while the base query used to order by
     * lll.lead_id. MySQL 8.4 rejects that combination with error 3065, which made segment SMS
     * broadcasts report "0 sent / 0 failed" instead of sending anything.
     */
    public function testGetPendingContactsReturnsSegmentContacts(): void
    {
        $segment = new LeadList();
        $segment->setName('SMS broadcast segment');
        $segment->setPublicName('SMS broadcast segment');
        $segment->setAlias('sms-broadcast-segment');
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $contact = new Lead();
        $contact->setFirstname('Broadcast');
        $contact->setLastname('Recipient');
        $contact->setMobile('123456789');
        $this->em->persist($contact);

        $listLead = new ListLead();
        $listLead->setLead($contact);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);

        $sms = $this->createAnSms('Broadcast SMS', 'Hello');
        $sms->setSmsType('list');
        $sms->addList($segment);
        $this->em->persist($sms);

        $this->em->flush();

        /** @var BroadcastQuery $broadcastQuery */
        $broadcastQuery = static::getContainer()->get(BroadcastQuery::class);

        $pendingContacts = $broadcastQuery->getPendingContacts($sms, new ContactLimiter(50));

        self::assertCount(1, $pendingContacts);
        self::assertSame($contact->getId(), (int) $pendingContacts[0]['id']);
        self::assertSame($segment->getId(), (int) $pendingContacts[0]['listId']);
        self::assertSame(1, (int) $broadcastQuery->getPendingCount($sms));
    }

    /**
     * MariaDB accepts the invalid ordering, so assert on the generated SQL as well to keep the
     * regression covered on every database platform in the test matrix.
     */
    public function testPendingContactsQueryOrdersBySelectedColumn(): void
    {
        $sms = $this->createAnSms('Broadcast SMS', 'Hello');
        $sms->setSmsType('list');
        $this->em->persist($sms);
        $this->em->flush();

        /** @var BroadcastQuery $broadcastQuery */
        $broadcastQuery = static::getContainer()->get(BroadcastQuery::class);

        $sql = $broadcastQuery->getBasicQuery($sms)->getSQL();

        self::assertStringEndsWith('ORDER BY l.id ASC', $sql);
    }
}
