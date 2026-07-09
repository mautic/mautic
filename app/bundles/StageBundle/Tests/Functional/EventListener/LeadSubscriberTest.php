<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadMergeEvent;
use Mautic\StageBundle\Entity\LeadStageLog;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\EventListener\LeadSubscriber;
use Mautic\StageBundle\Model\StageModel;

final class LeadSubscriberTest extends MauticMysqlTestCase
{
    private StageModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = self::getContainer()->get('mautic.stage.model.stage');
    }

    public function testUpdateLead(): void
    {
        $this->assertEmpty($this->em->getRepository(LeadStageLog::class)->findAll());
        $ipAddress = new IpAddress();
        $ipAddress->setIpAddress('13.13.13.13');
        $this->em->persist($ipAddress);

        $leadOne    = $this->createLead('one@example.com');
        $leadTwo    = $this->createLead('two@example.com');
        $stageOne   = $this->createStage('one', 10);
        $stageTwo   = $this->createStage('two', 50);
        $stageThree = $this->createStage('three', 100);

        $this->createLeadStageLog($leadOne, $stageOne, $ipAddress);
        $this->createLeadStageLog($leadTwo, $stageOne, $ipAddress);

        $this->createLeadStageLog($leadOne, $stageTwo, $ipAddress);
        $this->createLeadStageLog($leadTwo, $stageTwo, $ipAddress);

        $this->createLeadStageLog($leadTwo, $stageThree, $ipAddress);

        $this->assertCount(5, $this->em->getRepository(LeadStageLog::class)->findAll());

        $leadMergeEvent = new LeadMergeEvent($leadTwo, $leadOne);

        /** @var LeadSubscriber $subscriber */
        $subscriber = self::getContainer()->get(LeadSubscriber::class);

        $subscriber->onLeadMerge($leadMergeEvent);

        $this->assertCount(3, $this->em->getRepository(LeadStageLog::class)->findAll());
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);
        $lead->setPoints(10);

        $this->model->getRepository()->saveEntity($lead);

        return $lead;
    }

    private function createStage(string $name, int $weight): Stage
    {
        $stage = new Stage();
        $stage->setName($name);
        $stage->setWeight($weight);

        $this->model->getRepository()->saveEntity($stage);

        return $stage;
    }

    private function createLeadStageLog(Lead $lead, Stage $stage, IpAddress $ipAddress): void
    {
        $log = new LeadStageLog();
        $log->setLead($lead);
        $log->setIpAddress($ipAddress);
        $log->setStage($stage);
        $log->setDateFired(new \DateTime());

        $this->model->getRepository()->saveEntity($log);
    }
}
