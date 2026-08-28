<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CampaignRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @param int[] $pendingEventIndexes
     *
     * @dataProvider pendingEventsProvider
     */
    public function testGetCountsForPendingContacts(
        array $pendingEventIndexes,
        int $expectedCount,
        int $expectedMinContactIndex,
        int $expectedMaxContactIndex,
    ): void {
        $campaign = new Campaign();
        $campaign->setName('Pending contacts');

        $events = [
            $this->createEvent($campaign, 'First root event'),
            $this->createEvent($campaign, 'Second root event'),
        ];

        $contacts = [new Lead(), new Lead(), new Lead(), new Lead()];
        foreach ($contacts as $contactIndex => $contact) {
            $this->em->persist($contact);
            $campaignMember = $this->createCampaignMember($campaign, $contact);
            if (3 === $contactIndex) {
                $campaignMember->setRotation(2);
            }
            $this->em->persist($campaignMember);
        }

        $this->em->persist($campaign);
        $this->em->flush();

        foreach ([[0, 0], [0, 1], [1, 1], [3, 0]] as [$contactIndex, $eventIndex]) {
            $eventLog = new LeadEventLog();
            $eventLog->setCampaign($campaign);
            $eventLog->setEvent($events[$eventIndex]);
            $eventLog->setLead($contacts[$contactIndex]);
            $eventLog->setDateTriggered(new \DateTime());
            $this->em->persist($eventLog);
        }

        $this->em->flush();

        $repository = $this->em->getRepository(Campaign::class);
        \assert($repository instanceof CampaignRepository);

        $pendingEventIds = array_map(
            fn (int $eventIndex): int => (int) $events[$eventIndex]->getId(),
            $pendingEventIndexes,
        );
        $result = $repository->getCountsForPendingContacts(
            $campaign->getId(),
            $pendingEventIds,
            new ContactLimiter(100),
        );

        self::assertSame($expectedCount, $result->getCount());
        self::assertSame($contacts[$expectedMinContactIndex]->getId(), $result->getMinId());
        self::assertSame($contacts[$expectedMaxContactIndex]->getId(), $result->getMaxId());
    }

    /**
     * @return iterable<string, array{int[], int, int, int}>
     */
    public static function pendingEventsProvider(): iterable
    {
        yield 'no pending events includes all contacts' => [
            [],
            4,
            0,
            3,
        ];

        yield 'one pending event excludes only its matching log' => [
            [0],
            3,
            1,
            3,
        ];

        yield 'multiple pending events exclude logs for every matching event' => [
            [0, 1],
            2,
            2,
            3,
        ];
    }

    private function createEvent(Campaign $campaign, string $name): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName($name);
        $event->setType('lead.field_value');
        $event->setEventType(Event::TYPE_CONDITION);
        $this->em->persist($event);

        return $event;
    }

    private function createCampaignMember(Campaign $campaign, Lead $contact): CampaignMember
    {
        $campaignMember = new CampaignMember();
        $campaignMember->setCampaign($campaign);
        $campaignMember->setLead($contact);
        $campaignMember->setDateAdded(new \DateTime());

        return $campaignMember;
    }
}
