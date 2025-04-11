<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\NotificationBundle\Tests\NotificationTrait;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NotificationControllerTest extends MauticMysqlTestCase
{
    use NotificationTrait;

    /**
     * @var string
     */
    private const REST_API_ID = 'restApiID';

    /**
     * @var string
     */
    private const API_ID = 'apiID';

    /**
     * Smoke test to ensure the '/s/notifications' route loads.
     */
    public function testIndexRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/notifications');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * Smoke test to ensure the '/s/notifications/new' route loads.
     */
    public function testNewRouteSuccessfullyLoads(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/notifications/new');
        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testNewWebNotificationValidSubmit(): void
    {
        $crawler     = $this->client->request(Request::METHOD_GET, '/s/notifications/new');
        $formCrawler = $crawler->filter('form[name=notification]');
        $this->assertCount(1, $formCrawler);

        $form    = $formCrawler->form();
        $form->setValues([
            'notification[name]'      => 'Some Name',
            'notification[heading]'   => 'Some Heading',
            'notification[message]'   => 'some message',
        ]);
        $crawler = $this->client->submit($form);

        Assert::assertStringContainsString('Some Name has been created!', $crawler->text());
    }

    public function testNewWebNotificationValidationErrors(): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/notifications/new');
        $this->assertValidationErrors($crawler);
    }

    public function testEditWebNotificationValidationErrors(): void
    {
        $notification = $this->createNotification($this->em);
        $this->em->flush();
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_GET, '/s/notifications/edit/'.$notification->getid());
        $this->assertValidationErrors($crawler);
    }

    private function assertValidationErrors(Crawler $crawler): void
    {
        $formCrawler = $crawler->filter('form[name=notification]');
        $this->assertCount(1, $formCrawler);

        // test blank errors
        $form = $formCrawler->form();
        $form->setValues([
            'notification[name]'      => '',
            'notification[heading]'   => '',
            'notification[message]'   => '',
        ]);
        $crawler     = $this->client->submit($form);
        $formCrawler = $crawler->filter('form[name=notification]');
        $this->assertCount(1, $formCrawler);
        Assert::assertMatchesRegularExpression('/A name is required\./', $formCrawler->text());
        Assert::assertMatchesRegularExpression('/A heading is required\./', $formCrawler->text());
        Assert::assertMatchesRegularExpression('/A message is required\./', $formCrawler->text());
    }
}
