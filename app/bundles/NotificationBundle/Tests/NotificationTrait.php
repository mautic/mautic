<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Handler\MockHandler;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait NotificationTrait
{
    private MockHandler $transportMock;

    private function getMockHandler(ContainerInterface $container): MockHandler
    {
        return $container->get(MockHandler::class);
    }

    private function createNotification(EntityManagerInterface $em): Notification
    {
        $notification = new Notification();
        $notification->setName('Name 1');
        $notification->setHeading('Heading 1');
        $notification->setMessage('Message 1');
        $em->persist($notification);

        return $notification;
    }

    private function createCampaign(EntityManagerInterface $em): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Notification');
        $em->persist($campaign);

        return $campaign;
    }

    private function setupIntegration(ContainerInterface $container, EntityManagerInterface $em, string $apiId, string $restApiId): void
    {
        /** @var AbstractIntegration $integration */
        $integration = $container->get('mautic.helper.integration')
            ->getIntegrationObject('OneSignal');
        $integrationSettings = $integration->getIntegrationSettings();
        $integrationSettings->setIsPublished(true);
        $integration->encryptAndSetApiKeys([
            'app_id'       => $apiId,
            'rest_api_key' => $restApiId,
        ], $integrationSettings);
        $em->persist($integrationSettings);
    }
}
