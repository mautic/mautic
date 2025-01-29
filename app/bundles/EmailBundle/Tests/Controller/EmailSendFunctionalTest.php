<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class EmailSendFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['disable_trackable_urls'] = false;

        parent::setUp();
    }

    public function testSendEmailWithContact(): void
    {
        $segment = $this->createSegment('Segment A', 'seg-a');
        $leads   = $this->createContacts(2, $segment);
        $content = '<!DOCTYPE html><htm><body><a href="https://localhost">link</a>
                        <a id="{unsubscribe_url}">unsubscribe here</a>
                        <a href="{resubscribe_url}">resubscribe here</a>
                        </body></html>';
        $email = $this->createEmail(
            'test subject',
            [$segment->getId() => $segment],
            $content
        );
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
            $this->getMailerMessagesByToAddress('contact-flood-0@doe.com')[0],
            $this->getMailerMessagesByToAddress('contact-flood-1@doe.com')[0],
        ];

        foreach ($messages as $message) {
            $body = quoted_printable_decode($message->getBody()->bodyToString());
            preg_match('/<a href=\"([^\"]*)\">(.*)<\/a>/iU', $body, $match);
            Assert::assertArrayHasKey(1, $match, $body);
            parse_str(parse_url($match[1], PHP_URL_QUERY), $queryParams);
            $clickThrough = unserialize(base64_decode($queryParams['ct']));
            Assert::assertArrayHasKey($message->getTo()[0]->toString(), $leads);
            Assert::assertSame($leads[$message->getTo()[0]->toString()]->getId(), (int) $clickThrough['lead']);
        }

        // Sort messages by to address as the order can differ
        usort(
            $messages,
            static fn (MauticMessage $a, MauticMessage $b) => $a->getTo()[0]->toString() <=> $b->getTo()[0]->toString()
        );

        $unsubscribeUrlPattern = '/https?:\/\/[^\/]+\/email\/unsubscribe\/([0-9a-z]{20})/';
        $resubscribeUrlPattern = '/https?:\/\/[^\/]+\/email\/resubscribe\/([0-9a-z]{20})/';

        // First email:
        Assert::assertStringContainsString('contact-flood-0@doe.com', $messages[0]->toString());
        preg_match($unsubscribeUrlPattern, $messages[0]->getHtmlBody(), $unsubscribeMatches1);
        preg_match($resubscribeUrlPattern, $messages[0]->getHtmlBody(), $resubscribeMatches1);

        Assert::assertSame(20, strlen($unsubscribeMatches1[1]), $messages[0]->getHtmlBody());
        Assert::assertEquals($unsubscribeMatches1[1], $resubscribeMatches1[1], $messages[0]->getHtmlBody());

        // Second email:
        Assert::assertStringContainsString('contact-flood-1@doe.com', $messages[1]->toString());
        preg_match($unsubscribeUrlPattern, $messages[1]->getHtmlBody(), $unsubscribeMatches2);
        preg_match($resubscribeUrlPattern, $messages[1]->getHtmlBody(), $resubscribeMatches2);

        Assert::assertSame(20, strlen($unsubscribeMatches2[1]), $messages[1]->getHtmlBody());
        Assert::assertEquals($unsubscribeMatches2[1], $resubscribeMatches2[1], $messages[1]->getHtmlBody());

        // The email stat hashes cannot be the same in different emails:
        Assert::assertNotEquals($unsubscribeMatches1[1], $unsubscribeMatches2[1], $messages[0]->getHtmlBody());
    }

    /**
     * @param array<string, LeadList> $segments
     */
    private function createEmail(string $subject, array $segments, string $emailContent): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject($subject);
        $email->setEmailType('list');
        $email->setLists($segments);
        $email->setTemplate('Blank');
        $email->setCustomHtml($emailContent);
        $this->em->persist($email);

        return $email;
    }

    /**
     * @return array<string, Lead>
     */
    private function createContacts(int $count, LeadList $segment): array
    {
        $contacts = [];
        for ($i = 0; $i < $count; ++$i) {
            $contact = new Lead();
            $email   = "contact-flood-{$i}@doe.com";
            $contact->setEmail($email);
            $this->em->persist($contact);

            $this->addContactToSegment($segment, $contact);
            $contacts[$email] = $contact;
        }

        return $contacts;
    }

    private function createSegment(string $name, string $alias): LeadList
    {
        $segment = new LeadList();
        $segment->setName($name);
        $segment->setPublicName($name);
        $segment->setAlias($alias);
        $this->em->persist($segment);

        return $segment;
    }

    private function addContactToSegment(LeadList $segment, Lead $lead): void
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());

        $this->em->persist($listLead);
    }
}
