<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use PHPUnit\Framework\Assert;

final class JumpToActionTest extends MauticMysqlTestCase
{
    /**
     * @see https://github.com/mautic/mautic/pull/11568
     */
    public function testInfiniteLoop(): void
    {
        $contact = new Lead();
        $contact->setEmail('loop@expe.rt');
        $contact->setDateIdentified(new \DateTime());
        $contact->setLastActive(new \DateTime());

        $tag = new Tag();
        $tag->setTag('VisitedPageA');

        $decision = new Event();
        $decision->setOrder(1);
        $decision->setName('URL is hit');
        $decision->setType('page.pagehit');
        $decision->setEventType('decision');
        $decision->setProperties([
            'url' => 'https://mautic.org',
        ]);

        $addTag = new Event();
        $addTag->setOrder(2);
        $addTag->setParent($decision);
        $addTag->setName('Add tag');
        $addTag->setType('lead.changetags');
        $addTag->setEventType('action');
        $addTag->setTriggerInterval(1);
        $addTag->setTriggerIntervalUnit('i');
        $addTag->setTriggerMode('interval');
        $addTag->setDecisionPath('yes');
        $addTag->setProperties([
            'add_tags' => ['VisitedPageA'],
        ]);

        $jumpTo = new Event();
        $jumpTo->setOrder(2);
        $jumpTo->setParent($decision);
        $jumpTo->setName('Jump to');
        $jumpTo->setType('campaign.jump_to_event');
        $jumpTo->setEventType('action');
        $jumpTo->setTriggerInterval(1);
        $jumpTo->setTriggerIntervalUnit('i');
        $jumpTo->setTriggerMode('interval');
        $jumpTo->setDecisionPath('no');

        $campaign = new Campaign();
        $campaign->addEvents([$decision, $addTag, $jumpTo]);
        $campaign->setName('Campaign A');

        $campaignMember = new CampaignMember();
        $campaignMember->setLead($contact);
        $campaignMember->setCampaign($campaign);
        $campaignMember->setDateAdded(new \DateTime('-61 seconds'));

        $decision->setCampaign($campaign);
        $decision->addChild($addTag);
        $decision->addChild($jumpTo);
        $addTag->setCampaign($campaign);
        $jumpTo->setCampaign($campaign);
        $campaign->addLead(0, $campaignMember);

        $this->em->persist($campaign);
        $this->em->persist($decision);
        $this->em->persist($addTag);
        $this->em->persist($jumpTo);
        $this->em->persist($contact);
        $this->em->persist($campaignMember);
        $this->em->persist($tag);
        $this->em->flush();

        $jumpTo->setProperties(['jumpToEvent' => $addTag->getId()]);

        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => $decision->getId(),
                        'positionX' => '1080',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => $addTag->getId(),
                        'positionX' => '980',
                        'positionY' => '260',
                    ],
                    [
                        'id'        => $jumpTo->getId(),
                        'positionX' => '1220',
                        'positionY' => '260',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '860',
                        'positionY' => '1',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => $decision->getId(),
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => $decision->getId(),
                        'targetId' => $addTag->getId(),
                        'anchors'  => [
                            'source' => 'yes',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => $decision->getId(),
                        'targetId' => $jumpTo->getId(),
                        'anchors'  => [
                            'source' => 'no',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->persist($jumpTo);
        $this->em->flush();

        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => $campaign->getId()]);

        $eventLogs = $this->getEventLogsForContact($contact);

        Assert::assertCount(3, $eventLogs, '3 event logs should be scheduled to be executed in 1 minute');
        Assert::assertSame(['URL is hit', 'Jump to', 'Add tag'], $this->getEventNames($eventLogs));

        // Time travel 2 minutes into the future:
        foreach ($eventLogs as $eventLog) {
            $eventLog->setTriggerDate(new \DateTime('-2 minutes'));
            $eventLog->setDateTriggered(new \DateTime('-2 minutes'));
            $this->em->persist($eventLog);
        }

        $this->em->flush();
        $this->em->detach($eventLog);
        $this->em->detach($jumpTo);
        $this->em->detach($eventLog);
        $this->em->detach($decision);
        $this->em->detach($addTag);
        $this->em->detach($campaignMember);
        $this->em->detach($tag);

        // Executing the command for the second time should not schedule any new events:
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => $campaign->getId()]);

        $eventLogs = $this->getEventLogsForContact($contact);

        Assert::assertCount(3, $eventLogs); // This was 6 before the fix.
        Assert::assertSame(['URL is hit', 'Jump to', 'Add tag'], $this->getEventNames($eventLogs));
    }

    /**
     * @return LeadEventLog[]
     */
    private function getEventLogsForContact(Lead $contact): array
    {
        $eventLogRepository = $this->em->getRepository(LeadEventLog::class);

        return $eventLogRepository->findBy(['lead' => $contact->getId()]);
    }

    /**
     * @param LeadEventLog[] $eventLogs
     *
     * @return string[]
     */
    private function getEventNames(array $eventLogs): array
    {
        return array_map(
            fn (LeadEventLog $eventLog) => $eventLog->getEvent()->getName(),
            $eventLogs
        );
    }
}
