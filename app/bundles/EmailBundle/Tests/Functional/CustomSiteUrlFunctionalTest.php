<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;
use Symfony\Component\Mime\Message;

final class CustomSiteUrlFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->configParams['disable_trackable_urls'] = false;
        $this->configParams['site_url']               = 'https://my-new-mautic.com';
        parent::setUp();
    }

    public function testTrackedEmailLinkUsesConfiguredSiteUrl(): void
    {
        $lead    = $this->createLead('John', 'Doe', 'mtc-11550@example.com');
        $segment = $this->createSegment('mtc-11550-segment', []);
        $this->createListLead($segment, $lead);

        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Test subject for site url');
        $email->setEmailType('list');
        $email->setLists(['mtc-11550-segment' => $segment]);
        $email->setTemplate('Blank');
        $email->setCustomHtml('<!DOCTYPE html><html><body><a href="https://externalsite.org/page">Click Me</a></body></html>');
        $this->em->persist($email);
        $this->em->flush();
        $this->em->clear();

        /** @var MessageLoggerListener $messageLogger */
        $messageLogger = self::getContainer()->get('mailer.message_logger_listener'); // @phpstan-ignore mautic.noContainerGet
        $this->assertInstanceOf(MessageLoggerListener::class, $messageLogger);

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => 1],
        );

        self::assertResponseIsSuccessful();
        $this->assertStringContainsString('"sent":1', (string) $this->client->getResponse()->getContent());

        $messages = $messageLogger->getEvents()->getMessages();
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(Message::class, $messages[0]);

        $body = quoted_printable_decode($messages[0]->getBody()->bodyToString());
        preg_match('/<a href="([^"]+)">Click Me<\/a>/i', $body, $matches);

        $this->assertArrayHasKey(1, $matches, "Could not find the tracked link in the email body: {$body}");
        $this->assertSame('my-new-mautic.com', parse_url($matches[1], PHP_URL_HOST));
        $this->assertStringStartsWith('/r/', (string) parse_url($matches[1], PHP_URL_PATH));
    }
}
