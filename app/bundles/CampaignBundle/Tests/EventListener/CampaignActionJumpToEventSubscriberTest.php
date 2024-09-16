<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Result\Counter;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;

final class CampaignActionJumpToEventSubscriberTest extends TestCase
{
    public function testOnJumpToEventWhenEventDoesNotExist(): void
    {
        $event    = new Event();
        $campaign = new Campaign();
        $leadLog  = new class() extends LeadEventLog {
            public function getId()
            {
                return 456;
            }
        };
        $contact = new class() extends Lead {
            public function getId()
            {
                return 789;
            }
        };
        $leadLog->setLead($contact);

        $eventRepository = new class($campaign) extends EventRepository {
            private Campaign $campaign;

            public function __construct(Campaign $campaign)
            {
                $this->campaign = $campaign;
            }

            public function getEntities(array $args = [])
            {
                Assert::assertSame(
                    [
                        'ignore_paginator' => true,
                        'filter'           => [
                            'force' => [
                                [
                                    'column' => 'e.id',
                                    'value'  => 123,
                                    'expr'   => 'eq',
                                ],
                                [
                                    'column' => 'e.campaign',
                                    'value'  => $this->campaign,
                                    'expr'   => 'eq',
                                ],
                            ],
                        ],
                    ],
                    $args
                );

                return []; // No entity found.
            }
        };

        $eventExecutioner = new class() extends EventExecutioner {
            public function __construct()
            {
            }
        };
        $translator = new class() extends Translator {
            public function __construct()
            {
            }

            /**
             * @param mixed[] $parameters
             */
            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                Assert::assertSame('mautic.campaign.campaign.jump_to_event.target_not_exist', $id);

                return $id;
            }
        };
        $leadRepository = new class() extends LeadRepository {
            public function __construct()
            {
            }
        };
        $subscriber = new CampaignActionJumpToEventSubscriber(
            $eventRepository,
            $eventExecutioner,
            $translator,
            $leadRepository
        );

        $event->setProperties(['jumpToEvent' => 123]);
        $event->setCampaign($campaign);

        $pendingEvent = new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$leadLog->getId() => $leadLog]));

        $subscriber->onJumpToEvent($pendingEvent);

        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertCount(0, $pendingEvent->getFailures());

        Assert::AssertSame(
            [
                'failed' => 1,
                'reason' => 'mautic.campaign.campaign.jump_to_event.target_not_exist',
            ],
            $leadLog->getMetadata()
        );
    }

    public function testOnJumpToEventWhenEventExists(): void
    {
        $event    = new Event();
        $campaign = new class() extends Campaign {
            public function getId()
            {
                return 111;
            }
        };
        $leadLog = new class() extends LeadEventLog {
            public function getId()
            {
                return 456;
            }
        };
        $contact = new class() extends Lead {
            public function getId()
            {
                return 789;
            }
        };
        $leadLog->setLead($contact);

        $eventRepository = new class($campaign) extends EventRepository {
            private Campaign $campaign;

            public function __construct(Campaign $campaign)
            {
                $this->campaign = $campaign;
            }

            public function getEntities(array $args = [])
            {
                Assert::assertSame(
                    [
                        'ignore_paginator' => true,
                        'filter'           => [
                            'force' => [
                                [
                                    'column' => 'e.id',
                                    'value'  => 123,
                                    'expr'   => 'eq',
                                ],
                                [
                                    'column' => 'e.campaign',
                                    'value'  => $this->campaign,
                                    'expr'   => 'eq',
                                ],
                            ],
                        ],
                    ],
                    $args
                );

                return [
                    new class() extends Event {
                        public function getId()
                        {
                            return 222;
                        }
                    },
                ];
            }
        };

        $eventExecutioner = new class() extends EventExecutioner {
            public function __construct()
            {
            }

            public function executeForContacts(Event $event, ArrayCollection $contacts, ?Counter $counter = null, $isInactiveEvent = false)
            {
                Assert::assertSame(222, $event->getId());
                Assert::assertCount(1, $contacts);
                Assert::assertSame(789, $contacts->first()->getId());
            }
        };
        $translator = new class() extends Translator {
            public function __construct()
            {
            }
        };
        $leadRepository = new class() extends LeadRepository {
            public function __construct()
            {
            }

            public function incrementCampaignRotationForContacts(array $contactIds, $campaignId)
            {
                Assert::assertSame([789], $contactIds);
                Assert::assertSame(111, $campaignId);

                return true;
            }
        };
        $subscriber = new CampaignActionJumpToEventSubscriber(
            $eventRepository,
            $eventExecutioner,
            $translator,
            $leadRepository
        );

        $event->setProperties(['jumpToEvent' => 123]);
        $event->setCampaign($campaign);

        $pendingEvent = new PendingEvent(new ActionAccessor([]), $event, new ArrayCollection([$leadLog->getId() => $leadLog]));

        $subscriber->onJumpToEvent($pendingEvent);

        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::AssertSame([], $leadLog->getMetadata());
    }
}
