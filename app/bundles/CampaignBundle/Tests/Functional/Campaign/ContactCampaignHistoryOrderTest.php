<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Attributes\DataProvider;

final class ContactCampaignHistoryOrderTest extends MauticMysqlTestCase
{
    /**
     * @var bool
     */
    protected $useCleanupRollback = false;

    /**
     * @param string[] $expectedOrder
     * @param string[] $expectedFractions
     */
    #[DataProvider('provideMillisecondOrderData')]
    public function testTimelineOrdersEventsWithinTheSameSecond(string $direction, array $expectedOrder, array $expectedFractions): void
    {
        $contact = new Lead();
        $contact->setEmail('millisecond-order@mautic.com');
        $campaign = new Campaign();
        $campaign->setName('Millisecond Order');
        $this->em->persist($contact);
        $this->em->persist($campaign);

        // Chronological order (Charlie, Alpha, Bravo) differs from both name and ID order.
        foreach (['Bravo' => '300', 'Charlie' => '100', 'Alpha' => '200'] as $name => $milliseconds) {
            $event = new Event();
            $event->setName($name);
            $event->setType('lead.changepoints');
            $event->setEventType(Event::TYPE_ACTION);
            $event->setCampaign($campaign);
            $this->em->persist($event);

            $log = new LeadEventLog();
            $log->setLead($contact);
            $log->setCampaign($campaign);
            $log->setEvent($event);
            $log->setDateTriggered(new \DateTime('2025-11-28 12:00:00.'.$milliseconds, new \DateTimeZone('UTC')));
            $this->em->persist($log);
            // Allocate event and log IDs in the deliberately non-chronological insertion order.
            $this->em->flush();
        }

        $contactId = $contact->getId();
        $this->em->clear();
        $contact = $this->em->find(Lead::class, $contactId);

        $engagements = static::getContainer()->get(LeadModel::class)->getEngagements(
            $contact,
            ['search' => '', 'includeEvents' => ['campaign.event'], 'excludeEvents' => []],
            ['timestamp', $direction]
        );

        $names     = [];
        $seconds   = [];
        $fractions = [];
        foreach ($engagements['events'] as $event) {
            $names[]     = trim(explode('/', $event['eventLabel']['label'])[0]);
            $seconds[]   = $event['timestamp']->getTimestamp();
            $fractions[] = $event['timestamp']->format('u');
        }

        $this->assertSame($expectedOrder, $names);
        $this->assertSame($expectedFractions, $fractions);
        $this->assertSame(array_fill(0, 3, (new \DateTime('2025-11-28 12:00:00', new \DateTimeZone('UTC')))->getTimestamp()), $seconds);
    }

    /**
     * @return iterable<string, array{string, string[], string[]}>
     */
    public static function provideMillisecondOrderData(): iterable
    {
        yield 'ASC' => ['ASC', ['Charlie', 'Alpha', 'Bravo'], ['100000', '200000', '300000']];
        yield 'DESC' => ['DESC', ['Bravo', 'Alpha', 'Charlie'], ['300000', '200000', '100000']];
    }

    /**
     * @param string[] $expectedOrder
     */
    #[DataProvider('provideTimelineOrderData')]
    public function testContactCampaignHistoryOrderIsCorrectTimeline(string $orderByDir, array $expectedOrder): void
    {
        // Create a test segment
        $segment = new LeadList();
        $segment->setName('Test Segment');
        $segment->setPublicName('Test Segment');
        $segment->setAlias('test-segment');
        $this->em->persist($segment);
        $this->em->flush();

        // Create a campaign with action events
        $campaign = $this->createCampaignWithEvents($segment);
        $this->em->flush();

        // Create a contact & ensure included in the segment
        $contact = $this->createContactAndAddToSegment($segment);
        $this->em->flush();
        $this->em->clear();

        // Trigger campaign execution
        $this->testSymfonyCommand('mautic:campaigns:rebuild', ['-i' => $campaign->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => $campaign->getId()]);
        $this->em->clear();

        /** @var LeadModel $contactModal */
        $contactModal = static::getContainer()->get(LeadModel::class);

        $filters = [
            'search'        => '',
            'includeEvents' => ['campaign.event'],
            'excludeEvents' => [],
        ];
        $orderBy = ['timestamp', $orderByDir];

        $engagements    = $contactModal->getEngagements($contact, $filters, $orderBy);
        $timelineEvents = $engagements['events'];

        $historyOrder = [];
        foreach ($timelineEvents as $timelineEvent) {
            $historyOrder[] = trim(explode('/', $timelineEvent['eventLabel']['label'])[0]);
        }

        $this->assertSame($expectedOrder, $historyOrder, 'The campaign history is not sorted by creation order in '.$orderByDir);
    }

    /**
     * @return iterable<string, array{0: string, 1: string[]}>
     */
    public static function provideTimelineOrderData(): iterable
    {
        $expectedOrder = [
            'Update Contact',
            'Adjust Points',
            'Add to Company',
        ];

        yield 'ASC' => [
            'ASC',
            $expectedOrder,
        ];

        yield 'DESC' => [
            'DESC',
            array_reverse($expectedOrder),
        ];
    }

    private function createCampaignWithEvents(LeadList $segment): Campaign
    {
        // Dummy company for Add to Company action
        $company = new Company();
        $company->setName('Test Company');
        $this->em->persist($company);
        $this->em->flush();

        $campaign = new Campaign();
        $campaign->setName('History Order Test');
        $campaign->setIsPublished(true);
        $campaign->setPublishUp(new \DateTime('-1 day'));
        $campaign->addList($segment);

        // Action 1: Update Contact
        $event1 = new Event();
        $event1->setName('Update Contact');
        $event1->setType('lead.updatelead');
        $event1->setProperties(['tags' => ['test-tag']]);
        $event1->setEventType(Event::TYPE_ACTION);
        $event1->setCampaign($campaign);
        $campaign->addEvent(1, $event1);

        // Action 2: Adjust Points
        $event2 = new Event();
        $event2->setName('Adjust Points');
        $event2->setType('lead.changepoints');
        $event2->setProperties(['points' => 10]);
        $event2->setEventType(Event::TYPE_ACTION);
        $event2->setCampaign($campaign);
        $event2->setParent($event1);
        $campaign->addEvent(2, $event2);

        // Action 3: Add to Company
        $event3 = new Event();
        $event3->setName('Add to Company');
        $event3->setType('lead.addtocompany');
        $event3->setProperties(['company' => $company->getId()]);
        $event3->setEventType(Event::TYPE_ACTION);
        $event3->setCampaign($campaign);
        $event3->setParent($event2);
        $campaign->addEvent(3, $event3);

        $this->em->persist($campaign);

        return $campaign;
    }

    private function createContactAndAddToSegment(LeadList $segment): Lead
    {
        $contact = new Lead();
        $contact->setEmail('history-test@mautic.com');
        $this->em->persist($contact);
        $this->em->flush();

        /** @var ListModel $listModel */
        $listModel = static::getContainer()->get(ListModel::class);
        $listModel->addLead($contact, $segment);
        $this->em->flush();

        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segment->getId()]);
        $this->em->clear();

        return $contact;
    }
}
