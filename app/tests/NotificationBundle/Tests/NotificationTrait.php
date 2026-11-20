<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Handler\MockHandler;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait NotificationTrait
{
    private const string REST_API_ID = 'restApiID';

    private const string API_ID = 'apiID';

    private MockHandler $transportMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transportMock = $this->getMockHandler(static::getContainer());
        $this->setupIntegration(static::getContainer(), $this->em, self::API_ID, self::REST_API_ID);
    }

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
        $integrationSettings = $container->get(IntegrationRepository::class)->findOneBy(['name' => OneSignalIntegration::NAME])
            ?? new Integration()->setName(OneSignalIntegration::NAME);
        $integrationSettings->setIsPublished(true);
        $integrationSettings->setApiKeys($container->get(EncryptionService::class)->encrypt([
            'app_id'       => $apiId,
            'rest_api_key' => $restApiId,
        ]));
        $em->persist($integrationSettings);
    }
}
