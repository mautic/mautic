<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\PluginBundle\EventListener\LeadSubscriber;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LeadSubscriberTest extends TestCase
{
    private LeadSubscriber $subscriber;

    /**
     * @var MockObject&IntegrationEntityRepository
     */
    private MockObject $integrationEntityRepository;

    /**
     * @var MockObject&IntegrationRepository
     */
    private MockObject $integrationRepository;

    protected function setUp(): void
    {
        $this->integrationRepository       = $this->createMock(IntegrationRepository::class);
        $this->integrationEntityRepository = $this->createMock(IntegrationEntityRepository::class);
        $this->subscriber                  = new LeadSubscriber(
            $this->integrationEntityRepository,
            $this->integrationRepository
        );
    }

    public function testOnLeadSaveWithoutActiveIntegration(): void
    {
        $this->integrationRepository->expects($this->once())
            ->method('getIntegrations')
            ->willReturn([]);

        $this->integrationEntityRepository->expects($this->never())
            ->method('updateErrorLeads');

        $lead = new Lead();
        $this->subscriber->onLeadSave(new EntityPostSave($lead));
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

        $lead = new Lead();
        $this->subscriber->onLeadSave(new EntityPostSave($lead));
    }
}
