<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\LeadBundle\EventListener\LeadSubscriber;

class LeadSubscriberFunctionalTest extends MauticMysqlTestCase
{
    public function testUpdateLead(): void
    {
        // Create Leads
        $contactA = (new Lead())->setEmail('one@example.com');
        $this->em->persist($contactA);

        $contactB = (new Lead())->setEmail('two@example.com');
        $this->em->persist($contactB);

        $this->em->flush();

        // Create Segment
        $segmentA = $this->createSegment('Segment A', 'seg-a');
        $segmentB = $this->createSegment('Segment B', 'seg-b');
        $segmentC = $this->createSegment('Segment C', 'seg-c');

        // Add leads to segment
        $this->addContactToSegment($segmentA, $contactA);
        $this->addContactToSegment($segmentA, $contactB);
        $this->addContactToSegment($segmentB, $contactB);
        $this->addContactToSegment($segmentC, $contactA);
        $this->addContactToSegment($segmentC, $contactB);

        $this->em->clear();

        $leadMergeEvent = new LeadMergeEvent($contactB, $contactA);

        $subscriber = self::getContainer()->get(LeadSubscriber::class);
        \assert($subscriber instanceof LeadSubscriber);

        $subscriber->onLeadMerge($leadMergeEvent);

        $this->em->clear();

        $prefix = self::getContainer()->getParameter('mautic.db_table_prefix');
        $count  = $this->connection->fetchNumeric("SELECT count(lead_id) FROM {$prefix}lead_lists_leads WHERE leadlist_id = :id", ['id' => $segmentC->getId()]);

        $this->assertNotEmpty($count);
        $this->assertEquals(1, $count[0]);
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);

        $this->em->persist($segment);

        return $segment;
    }

    private function addContactToSegment(LeadList $segment, Lead $lead): void
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());

        $this->em->persist($listLead);
        $this->em->flush();
    }
}
