<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\Assert;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SearchSubscriberFunctionalTest extends MauticMysqlTestCase
{
    private Email $email;

    public function testPendingEmailContactListQuery(): void
    {
        $this->prepareTestData();
        $this->em->flush();

        $alias = 'test';
        $qb    = $this->em->getConnection()->createQueryBuilder();
        $event = new LeadBuildSearchEvent((string) $this->email->getId(), 'email_pending', $alias, false, $qb);

        $dispatcher = self::getContainer()->get('event_dispatcher');
        $this->assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $expectedQuery = 'l.id IN (SELECT l.id FROM '.MAUTIC_TABLE_PREFIX.'leads l WHERE (l.id IN (SELECT ll.lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (:listIds)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM '.MAUTIC_TABLE_PREFIX."lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM ".MAUTIC_TABLE_PREFIX.'email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id IN (:variantIds)))) AND (l.id NOT IN (SELECT mq.lead_id FROM '.MAUTIC_TABLE_PREFIX."message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id IN (:variantIds)))) AND (l.id NOT IN (SELECT lc.lead_id FROM ".MAUTIC_TABLE_PREFIX.'lead_categories lc INNER JOIN '.MAUTIC_TABLE_PREFIX."emails e ON e.category_id = lc.category_id WHERE (e.id = {$this->email->getId()}) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> '')))";

        Assert::assertSame($expectedQuery, $event->getSubQuery());
    }

    public function testPendingEmailContactListPage(): void
    {
        $this->prepareTestData();
        $this->em->flush();

        $crawler = $this->client->request('GET', sprintf('/s/contacts?search=email_pending:%d', $this->email->getId()));
        self::assertResponseIsSuccessful();

        $text = $crawler->text();
        Assert::assertStringContainsString('Lead-test-1', $text);
        Assert::assertStringContainsString('Lead-test-2', $text);
        Assert::assertStringNotContainsString('Lead-test-3', $text);
    }

    private function prepareTestData(): void
    {
        $lead1 = $this->createLead('Lead-test-1');
        $lead2 = $this->createLead('Lead-test-2');
        $this->createLead('Lead-test-3');

        $filters = [
            [
                'glue'       => 'and',
                'field'      => 'lastname',
                'object'     => 'lead',
                'type'       => 'text',
                'properties' => ['filter' => 'L'],
                'operator'   => 'startsWith',
            ],
        ];

        $segment = $this->createSegment($filters);
        $this->addContactToSegment($lead1, $segment);
        $this->addContactToSegment($lead2, $segment);

        $this->email = $this->createEmailWithIncludedLists($segment);
    }

    private function createLead(string $lastName): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $lead->setLastname($lastName);
        $lead->setEmail($lastName.'@mail.tld');
        $this->em->persist($lead);

        return $lead;
    }

    /**
     * @param array<mixed> $filters
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createSegment(array $filters = []): LeadList
    {
        $segment = new LeadList();
        $segment->setFilters($filters);
        $segment->setName('Segment A');
        $segment->setPublicName('Segment A');
        $segment->setAlias('segment-a');
        $this->em->persist($segment);

        return $segment;
    }

    private function addContactToSegment(Lead $contact, LeadList $segment): ListLead
    {
        $leadList = new ListLead();
        $leadList->setList($segment);
        $leadList->setLead($contact);
        $leadList->setDateAdded(new \DateTime());
        $this->em->persist($leadList);

        return $leadList;
    }

    private function createEmailWithIncludedLists(LeadList $segment): Email
    {
        $email = new Email();
        $email->setName('Email 1');
        $email->setSubject('Subject 1');
        $email->setUuid(Uuid::uuid4()->toString());
        $email->setDateAdded(new \DateTime());
        $email->setPublicPreview(true);
        $email->setCustomHtml(json_encode(''));
        $email->setEmailType('list');

        $email->addList($segment);

        $this->em->persist($email);

        return $email;
    }
}
