<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
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
