<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Traits\LoggerTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\LeadEvents;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EventExecutionerLockTest extends MauticMysqlTestCase
{
    use LoggerTrait {
        setUp as loggerTraitSetup;
    }

    private const ADD_POINTS = 10;
    private EventExecutioner $eventExecutioner;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void // @phpstan-ignore phpunit.callParent
    {
        $this->loggerTraitSetup();

        $this->eventExecutioner = self::getContainer()->get('mautic.campaign.event_executioner');
        $this->eventDispatcher  = self::getContainer()->get('event_dispatcher');
    }

    public function testLogsAreSkippedWhenAlreadyExecuted(): void
    {
        $event    = $this->createEvent($this->createCampaign());
        $contact  = $this->createContact();
        $this->em->flush();

        Assert::assertSame(0, $contact->getPoints());

        $contacts = new ArrayCollection([$contact->getId() => $contact]);
        $this->eventExecutioner->executeForContacts($event, $contacts);
        Assert::assertSame(self::ADD_POINTS, $contact->getPoints(), 'Points should be added.');

        $logs = $this->em->getRepository(LeadEventLog::class)->findAll();
        Assert::assertCount(1, $logs);

        $log = reset($logs);
        \assert($log instanceof LeadEventLog);
        Assert::assertSame(2, $log->getVersion(), 'Version should be incremented.');

        $this->eventExecutioner->executeLogs($event, new ArrayCollection($logs));
        Assert::assertSame(self::ADD_POINTS, $contact->getPoints(),  // @phpstan-ignore argument.unresolvableType
            'Points should not be added as the log has been executed already.');
        Assert::assertTrue($this->testHandler->hasErrorThatContains(sprintf(
            'Campaign event log ID "%s" was skipped as it had been executed already.',
            $log->getId(),
        )), 'There should be an error log regarding the skipped log.');
    }

    public function testFailedLogsCanBeReExecuted(): void
    {
        $event    = $this->createEvent($this->createCampaign());
        $contact  = $this->createContact();
        $this->em->flush();

        Assert::assertSame(0, $contact->getPoints());

        $listener = $this->makeEventExecutionFail();
        $contacts = new ArrayCollection([$contact->getId() => $contact]);
        $this->eventExecutioner->executeForContacts($event, $contacts);
        Assert::assertSame(0, $contact->getPoints(),
            'Points should not be added as the execution failed.');

        $logs = $this->em->getRepository(LeadEventLog::class)->findAll();
        Assert::assertCount(1, $logs);

        $log = reset($logs);
        \assert($log instanceof LeadEventLog);
        Assert::assertSame(1, $log->getVersion(), 'Version should be reset when execution failed.');

        $this->makeEventExecutionPass($listener);
        $this->eventExecutioner->executeLogs($event, new ArrayCollection($logs));
        Assert::assertSame(self::ADD_POINTS, $contact->getPoints(),
            'Points should be added as the log\'s version has been reset when execution failed.');
        Assert::assertFalse($this->testHandler->hasWarningThatContains(sprintf(
            'Campaign event log ID "%s" was skipped as it had been executed already.',
            $log->getId(),
        )), 'There should not be any warning log regarding skipped logs.');
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Add points');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setProperties(['points' => self::ADD_POINTS]);
        $this->em->persist($event);

        return $event;
    }

    private function createContact(): Lead
    {
        $contact = new Lead();
        $this->em->persist($contact);

        return $contact;
    }

    private function makeEventExecutionFail(): callable
    {
        $listener = function (CampaignExecutionEvent $event) { // @phpstan-ignore parameter.deprecatedClass
            $event->setResult(false);
            $event->stopPropagation();
        };
        $this->eventDispatcher->addListener(LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION, $listener, 9999); // @phpstan-ignore classConstant.deprecated

        return $listener;
    }

    private function makeEventExecutionPass(callable $listener): void
    {
        $this->eventDispatcher->removeListener(LeadEvents::ON_CAMPAIGN_TRIGGER_ACTION, $listener); // @phpstan-ignore classConstant.deprecated
    }
}
