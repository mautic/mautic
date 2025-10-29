<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CustomSiteUrlFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->configParams['site_url'] = 'https://my-new-mautic.com';
        parent::setUp();
    }

    /**
     * This test verifies that tracked links within an email are generated
     * using the currently configured `site_url`.
     */
    public function testRedirectLinkUsesUpdatedSiteUrl(): void
    {
        $expectedHost = 'my-new-mautic.com';

        // 2. Create the necessary entities for sending an email.
        $lead    = $this->createLead('John', 'Doe', 'test@example.com');
        $segment = $this->createSegment('Segment B', []);
        $this->em->flush();
        $this->createListLead($segment, $lead);
        $this->em->flush();

        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject('Test subject for site url');
        $email->setEmailType('list');
        $email->setLists([$segment->getId() => $segment]);
        $email->setTemplate('Blank');
        $email->setCustomHtml('<!DOCTYPE html><html><body><a href="https://externalsite.org/page">Click Me</a></body></html>');
        $this->em->persist($email);
        $this->em->flush();
        $this->em->clear();

        // 3. Send the email via the ajax batch endpoint.
        $this->client->request(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => 1],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        Assert::assertStringContainsString('"sent":1', $response->getContent());

        // 4. Verify the sent email's redirect link.
        $messages = $this->getMailerMessagesByToAddress($lead->getEmail());
        Assert::assertCount(1, $messages, 'Expected one email to be sent.');

        $message = $messages[0];
        $body    = quoted_printable_decode($message->getBody()->bodyToString());

        // Find the tracked link in the email body.
        preg_match('/<a href="([^"]+)">Click Me<\/a>/i', $body, $matches);
        Assert::assertArrayHasKey(1, $matches, "Could not find the tracked link in the email body: {$body}");

        $redirectUrl = $matches[1];

        // The parsed host from the redirect URL should match our newly configured site_url host.
        $urlParts = parse_url($redirectUrl);
        Assert::assertIsArray($urlParts, "Failed to parse the redirect URL {$redirectUrl}");
        Assert::assertSame($expectedHost, $urlParts['host'], 'The host of the redirect link does not match the updated site_url.');
        Assert::assertStringStartsWith('/r/', $urlParts['path'], 'The redirect link path is not in the expected format.');
    }
}
