<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\PointBundle\Entity\LeadPointLog;
use Mautic\PointBundle\Entity\LeadTriggerLog;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\EventListener\LeadSubscriber;
use Mautic\PointBundle\Model\PointModel;

final class LeadSubscriberTest extends MauticMysqlTestCase
{
    private PointModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = self::getContainer()->get('mautic.point.model.point');
    }

    public function testLeadPointLogUpdateLead(): void
    {
        $this->assertEmpty($this->em->getRepository(LeadPointLog::class)->findAll());

        $contactA = $this->createLead('one@example.com');
        $contactB = $this->createLead('two@example.com');

        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('13.13.13.13');
        $this->em->persist($ipAddress);

        $pointRead = $this->createPoint('email.read', 100);
        $pointOpen = $this->createPoint('email.open', 100);

        $this->createLeadPointLog($pointRead, $contactA, $ipAddress);
        $this->createLeadPointLog($pointRead, $contactB, $ipAddress);
        $this->createLeadPointLog($pointOpen, $contactA, $ipAddress);

        $this->assertCount(3, $this->em->getRepository(LeadPointLog::class)->findAll());

        $leadMergeEvent = new LeadMergeEvent($contactA, $contactB);

        /** @var LeadSubscriber $subscriber */
        $subscriber = self::getContainer()->get(LeadSubscriber::class);

        $subscriber->onLeadMerge($leadMergeEvent);

        $this->assertCount(2, $this->em->getRepository(LeadPointLog::class)->findAll());
    }

    public function testLeadTriggerLogUpdateLead(): void
    {
        $this->assertEmpty($this->em->getRepository(LeadTriggerLog::class)->findAll());

        $contactA = $this->createLead('one@example.com');
        $contactB = $this->createLead('two@example.com');

        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('13.13.13.13');
        $this->em->persist($ipAddress);

        $trigger = new Trigger();
        $trigger->setName('One');
        $trigger->setPoints(100);
        $this->em->persist($trigger);

        $triggerEventOne = $this->createTriggerEvent('random.one', $trigger);
        $triggerEventTwo = $this->createTriggerEvent('random.two', $trigger);

        $this->createLeadTriggerLog($triggerEventOne, $contactA, $ipAddress);
        $this->createLeadTriggerLog($triggerEventOne, $contactB, $ipAddress);
        $this->createLeadTriggerLog($triggerEventTwo, $contactA, $ipAddress);

        $this->assertCount(3, $this->em->getRepository(LeadTriggerLog::class)->findAll());

        $leadMergeEvent = new LeadMergeEvent($contactA, $contactB);

        /** @var LeadSubscriber $subscriber */
        $subscriber = self::getContainer()->get(LeadSubscriber::class);

        $subscriber->onLeadMerge($leadMergeEvent);

        $this->assertCount(2, $this->em->getRepository(LeadTriggerLog::class)->findAll());
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setPoints(10);

        $this->model->getRepository()->saveEntity($lead);

        return $lead;
    }

    private function createPoint(string $type, int $delta): Point
    {
        $point = new Point();
        $point->setName($type);
        $point->setType($type);
        $point->setDelta($delta);
        $point->isPublished(true);
        $point->setRepeatable(true);

        $this->model->getRepository()->saveEntity($point);

        return $point;
    }

    private function createLeadPointLog(Point $point, Lead $lead, IpAddress $ipAddress): LeadPointLog
    {
        $log = new LeadPointLog();
        $log->setPoint($point);
        $log->setLead($lead);
        $log->setIpAddress($ipAddress);
        $log->setDateFired(new \DateTime());

        $this->model->getRepository()->saveEntity($log);

        return $log;
    }

    private function createTriggerEvent(string $type, Trigger $trigger): TriggerEvent
    {
        $event = new TriggerEvent();
        $event->setType($type);
        $event->setName($type);
        $event->setTrigger($trigger);

        $this->model->getRepository()->saveEntity($event);

        return $event;
    }

    private function createLeadTriggerLog(TriggerEvent $event, Lead $lead, IpAddress $ipAddress): void
    {
        $log = new LeadTriggerLog();
        $log->setEvent($event);
        $log->setLead($lead);
        $log->setIpAddress($ipAddress);
        $log->setDateFired(new \DateTime());

        $this->model->getRepository()->saveEntity($log);
    }
}
