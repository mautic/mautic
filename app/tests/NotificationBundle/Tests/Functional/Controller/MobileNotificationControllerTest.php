<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\NotificationBundle\Entity\Notification;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use Mautic\PluginBundle\Entity\Integration;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class MobileNotificationControllerTest extends MauticMysqlTestCase
{
    /**
     * Smoke test to ensure the '/s/mobile_notifications' route loads.
     */
    public function testIndexRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mobile_notifications');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testCreateRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * The iOS and Android tabs follow the platforms saved in OneSignal's feature settings.
     */
    #[DataProvider('editActionProvider')]
    public function testEditAndCloneShowTabsForConfiguredPlatforms(string $action): void
    {
        $settings = new Integration();
        $settings->setName(OneSignalIntegration::NAME);
        $settings->setFeatureSettings(['integration' => ['platforms' => ['ios']]]);
        $this->em->persist($settings);

        $notification = new Notification();
        $notification->setName('Mobile notification');
        $notification->setHeading('Heading');
        $notification->setMessage('Message');
        $notification->setMobile(true);
        $this->em->persist($notification);
        $this->em->flush();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/mobile_notifications/'.$action.'/'.$notification->getId());
        $this->assertResponseIsSuccessful();

        $this->assertCount(1, $crawler->filter('a[href="#ios-notification-container"]'));
        $this->assertCount(0, $crawler->filter('a[href="#android-notification-container"]'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function editActionProvider(): iterable
    {
        yield 'edit' => ['edit'];
        yield 'clone' => ['clone'];
    }
}
