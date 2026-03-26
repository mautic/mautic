<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\LeadList;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailSendDisabledTrackingFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    protected function setUp(): void
    {
        $this->configParams['disable_trackable_urls'] = true;

        parent::setUp();
    }

    public function testSendEmailWithContact(): void
    {
        $segment = $this->createSegment('Segment A', []);

        $utmParameters = [
            'utmSource'   => 'utmSourceA',
            'utmMedium'   => 'utmMediumA',
            'utmCampaign' => 'utmCampaignA',
            'utmContent'  => 'utmContentA',
        ];

        $leads                            = [];
        $leads['contact-flood-1@doe.com'] = $this->createLead('', '', 'contact-flood-1@doe.com');
        $this->createListLead($segment, $leads['contact-flood-1@doe.com']);

        $leads['contact-flood-2@doe.com'] = $this->createLead('', '', 'contact-flood-2@doe.com');
        $this->createListLead($segment, $leads['contact-flood-2@doe.com']);

        $content = '<!DOCTYPE html><htm><body><a href="https://localhost">link</a>
                        <a id="{unsubscribe_url}">unsubscribe here</a>
                        <a href="{resubscribe_url}">resubscribe here</a>
                        </body></html>';
        $email = $this->createSegmentEmail(
            'test subject',
            [$segment->getId() => $segment],
            $content
        );

        $email->setUtmTags($utmParameters);

        $this->em->flush();
        $this->em->clear();

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => 2]
        );

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful($response->getContent());
        Assert::assertSame(
            '{"success":1,"percent":100,"progress":[2,2],"stats":{"sent":2,"failed":0,"failedRecipients":[]}}',
            $response->getContent()
        );

        $messages = [
            self::getMailerMessagesByToAddress('contact-flood-1@doe.com')[0],
            self::getMailerMessagesByToAddress('contact-flood-2@doe.com')[0],
        ];

        foreach ($messages as $message) {
            assert($message instanceof MauticMessage);
            $body = quoted_printable_decode($message->getBody()->bodyToString());
            preg_match('/<a href=\"([^\"]*)\">(.*)<\/a>/iU', $body, $match);
            Assert::assertArrayHasKey(1, $match, $body);
            $urlQuery = parse_url($match[1], PHP_URL_QUERY);
            Assert::assertIsString($urlQuery, $body);
            $queryParams = [];
            parse_str($urlQuery, $queryParams);
            Assert::assertCount(4, $queryParams, json_encode($queryParams, JSON_THROW_ON_ERROR));
            Assert::assertSame($utmParameters['utmSource'], $queryParams['utm_source']);
            Assert::assertSame($utmParameters['utmMedium'], $queryParams['utm_medium']);
            Assert::assertSame($utmParameters['utmCampaign'], $queryParams['utm_campaign']);
            Assert::assertSame($utmParameters['utmContent'], $queryParams['utm_content']);
            $toList = array_values($message->getTo());
            Assert::assertNotEmpty($toList);
            Assert::assertArrayHasKey($toList[0]->toString(), $leads);
        }
    }

    /**
     * @param array<string, LeadList> $segments
     */
    private function createSegmentEmail(string $subject, array $segments, string $emailContent): Email
    {
        $email = $this->createEmail('Email name');

        $email->setDateAdded(new \DateTime());
        $email->setSubject($subject);
        $email->setEmailType('list');
        $email->setLists($segments);
        $email->setTemplate('Blank');
        $email->setCustomHtml($emailContent);
        $this->em->persist($email);

        return $email;
    }
}
