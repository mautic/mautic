<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\EventListener\CampaignSubscriber;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CampaignSubscriberTest extends TestCase
{
    public function testOnCampaignTriggerStageChangeWhenStageNotFound(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }
        };
        $log = new LeadEventLog();
        $log->setLead($contact);
        $config       = new ActionAccessor([]);
        $event        = new Event();
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null)
            {
                Assert::assertSame(123, $id);

                return null;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame(
            [
                'failed' => 1,
                'reason' => '[trans]mautic.stage.campaign.event.stage_missing[/trans]',
            ],
            $log->getMetadata()
        );
    }

    public function testOnCampaignTriggerStageChangeWhenStageUnpublished(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }
        };
        $log = new LeadEventLog();
        $log->setLead($contact);
        $config       = new ActionAccessor([]);
        $event        = new Event();
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null): \AnonymousClass0ca84960bc70b35c728ee8f329b40583
            {
                Assert::assertSame(123, $id);

                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };

                $stage->setIsPublished(false);

                return $stage;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame(
            [
                'failed' => 1,
                'reason' => '[trans]mautic.stage.campaign.event.stage_missing[/trans]',
            ],
            $log->getMetadata()
        );
    }

    public function testOnCampaignTriggerStageChangeWhenContactHasNoStage(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }
        };
        $campaign = new Campaign();
        $log      = new LeadEventLog();
        $log->setLead($contact);
        $config = new ActionAccessor([]);
        $event  = new Event();
        $event->setCampaign($campaign);
        $event->setName('Event A');
        $log->setEvent($event);
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }

            public function saveEntity($entity, $unlock = true): void
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null): \AnonymousClassed19c7a8b0e18c171d8aee050bbad59c
            {
                Assert::assertSame(123, $id);

                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };

                $stage->setIsPublished(true);

                return $stage;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame([], $log->getMetadata());
        Assert::assertSame(123, $contact->getStage()->getId());
        Assert::assertSame(['stage' => [null, 123]], $contact->getChanges());
    }

    public function testOnCampaignTriggerStageChangeWhenContactHasTheSameStage(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }

            public function getStage(): Stage
            {
                return new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };
            }
        };
        $campaign = new Campaign();
        $log      = new LeadEventLog();
        $log->setLead($contact);
        $config = new ActionAccessor([]);
        $event  = new Event();
        $event->setCampaign($campaign);
        $event->setName('Event A');
        $log->setEvent($event);
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null): \AnonymousClass399f6307f5d29cf21bd4c28190de6589
            {
                Assert::assertSame(123, $id);

                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };

                $stage->setIsPublished(true);

                return $stage;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame(
            [
                'failed' => 1,
                'reason' => '[trans]mautic.stage.campaign.event.already_in_stage[/trans]',
            ],
            $log->getMetadata()
        );
        Assert::assertSame(123, $contact->getStage()->getId());
        Assert::assertSame([], $contact->getChanges());
    }

    public function testOnCampaignTriggerStageChangeWhenContactHasStageWithGreaterWeight(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }

            public function getStage(): \AnonymousClass32754426480188f06ae6a6b9679fe04b
            {
                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 444;
                    }
                };

                $stage->setWeight(20);

                return $stage;
            }
        };
        $campaign = new Campaign();
        $log      = new LeadEventLog();
        $log->setLead($contact);
        $config = new ActionAccessor([]);
        $event  = new Event();
        $event->setCampaign($campaign);
        $event->setName('Event A');
        $log->setEvent($event);
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null): \AnonymousClass3cbc08961713e1770b34658eaeef5a57
            {
                Assert::assertSame(123, $id);

                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };

                $stage->setWeight(10);
                $stage->setIsPublished(true);

                return $stage;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame(
            [
                'failed' => 1,
                'reason' => '[trans]mautic.stage.campaign.event.stage_invalid[/trans]',
            ],
            $log->getMetadata()
        );
        Assert::assertSame(444, $contact->getStage()->getId());
        Assert::assertSame([], $contact->getChanges());
    }

    public function testOnCampaignTriggerStageChangeWhenContactHasStageWithLowerWeight(): void
    {
        $contact = new class() extends Lead {
            public function getId(): int
            {
                return 333;
            }

            public function getStage(): \AnonymousClass6dfef4e5fd7bc059dfbb8f91ed767cb5
            {
                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 444;
                    }
                };

                $stage->setWeight(10);

                return $stage;
            }
        };
        $campaign = new Campaign();
        $log      = new LeadEventLog();
        $log->setLead($contact);
        $config = new ActionAccessor([]);
        $event  = new Event();
        $event->setCampaign($campaign);
        $event->setName('Event A');
        $log->setEvent($event);
        $logs         = new ArrayCollection([$log]);
        $pendingEvent = new PendingEvent($config, $event, $logs);

        $event->setProperties(['stage' => 123]);

        $contactModel = new class() extends LeadModel {
            public function __construct()
            {
            }

            public function saveEntity($entity, $unlock = true): void
            {
            }
        };

        $stageModel = new class() extends StageModel {
            public function __construct()
            {
            }

            public function getEntity($id = null): \AnonymousClass6d7fa2111d9035b9642be19e071ea604
            {
                Assert::assertSame(123, $id);

                $stage = new class() extends Stage {
                    public function getId(): int
                    {
                        return 123;
                    }
                };

                $stage->setWeight(20);
                $stage->setIsPublished(true);

                return $stage;
            }
        };

        $subscriber = new CampaignSubscriber($contactModel, $stageModel, $this->createTranslatorMock());

        $subscriber->onCampaignTriggerStageChange($pendingEvent);

        Assert::assertCount(0, $pendingEvent->getFailures());
        Assert::assertCount(1, $pendingEvent->getPending());
        Assert::assertCount(1, $pendingEvent->getSuccessful());
        Assert::assertSame([], $log->getMetadata());
        Assert::assertSame(444, $contact->getStage()->getId());
        Assert::assertSame(['stage' => [444, 123]], $contact->getChanges());
    }

    private function createTranslatorMock(): TranslatorInterface
    {
        return new class() implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
            {
                return '[trans]'.$id.'[/trans]';
            }
        };
    }
}
