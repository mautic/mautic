<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\PluginBundle\EventListener\LeadSubscriber;
use Mautic\PluginBundle\Model\PluginModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LeadSubscriberTest extends TestCase
{
    private LeadSubscriber $subscriber;

    /**
     * @var IntegrationEntityRepository|MockObject
     */
    private $integrationEntityRepository;

    /**
     * @var IntegrationRepository|MockObject
     */
    private $integrationRepository;

    protected function setUp(): void
    {
        $pluginModel                       = $this->createMock(PluginModel::class);
        $this->integrationRepository       = $this->createMock(IntegrationRepository::class);
        $this->integrationEntityRepository = $this->createMock(IntegrationEntityRepository::class);
        $this->subscriber                  = new LeadSubscriber(
            $pluginModel,
            $this->integrationRepository
        );

        $pluginModel->expects($this->once())
            ->method('getIntegrationEntityRepository')
            ->willReturn($this->integrationEntityRepository);
    }

    public function testOnLeadSaveWithoutActiveIntegration(): void
    {
        $this->integrationRepository->expects($this->once())
            ->method('getIntegrations')
            ->willReturn([]);

        $this->integrationEntityRepository->expects($this->never())
            ->method('updateErrorLeads');

        $this->subscriber->onLeadSave(new LeadEvent(new Lead()));
    }

    public function testOnLeadSaveWithActiveIntegration(): void
    {
        $integration = new Integration();
        $integration->setIsPublished(true);
        $integration->setApiKeys(['key' => 'some']);
        $integration->setSupportedFeatures(['push_lead']);

        $this->integrationRepository->expects($this->once())
            ->method('getIntegrations')
            ->willReturn([$integration]);

        $this->integrationEntityRepository->expects($this->once())
            ->method('updateErrorLeads');

        $this->subscriber->onLeadSave(new LeadEvent(new Lead()));
    }
}
