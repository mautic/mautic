<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class BuilderSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['disable_trackable_urls'] = false;
        if (str_contains($this->getDataSetAsString(false), 'Invalid unsubscribe_text configured')) {
            $this->configParams['unsubscribe_text']  = '<a href="|some|">Unsubscribe</a> with invalid token within the href attribute.';
        }

        if (str_contains($this->getDataSetAsString(false), 'No unsubscribe_text configured')) {
            $this->configParams['unsubscribe_text']  = '';
        }

        $this->configParams['mailer_spool_type'] = 'file';
        parent::setUp();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function dataOneTrackingLinkIsNotUsedForDifferentContacts(): iterable
    {
        yield 'Invalid unsubscribe_text configured' => ['<!DOCTYPE html><htm><body><a href="https://localhost">link</a></body></html>'];
        yield 'No unsubscribe_text configured' => ['<!DOCTYPE html><htm><body><a href="https://localhost">link</a></body></html>'];
        yield 'Invalid tag attribute for unsubscribe_url' => ['<!DOCTYPE html><htm><body><a href="https://localhost">link</a><a id="{unsubscribe_url}">unsubscribe</a></body></html>'];
    }

    /**
     * @dataProvider dataOneTrackingLinkIsNotUsedForDifferentContacts
     */
    public function testOneTrackingLinkIsNotUsedForDifferentContacts(string $content): void
    {
        $numContacts = 3;
        $segment     = $this->createSegment('Segment', 'segment');
        $leads       = $this->createContacts($numContacts, $segment);
        $email       = $this->createEmail('Email subject', $segment, $content);
        $this->em->flush();
        $this->em->clear();

        $this->sendMessages($email, $numContacts);
        $this->assertQueuedEmailCount(3);

        foreach ($this->getMailerMessages() as $message) {
            $clickThrough = $this->parseClickThrough($message->getHtmlBody());
            $email        = $message->getTo()[0]->getAddress();
            Assert::assertSame((string) $leads[$email]->getId(), $clickThrough['lead'], '"lead" parameter within the click through should match the contact\'s ID.');
        }
    }

    private function createEmail(string $subject, LeadList $segment, string $emailContent): Email
    {
        $email = new Email();
        $email->setDateAdded(new \DateTime());
        $email->setName('Email name');
        $email->setSubject($subject);
        $email->setEmailType('list');
        $email->setLists([$segment]);
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

    private function sendMessages(Email $email, int $pending): void
    {
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => $pending],
        );

        $response = $this->client->getResponse();
        self::assertResponseIsSuccessful($response->getContent());
        Assert::assertSame(
            '{"success":1,"percent":100,"progress":['.$pending.','.$pending.'],"stats":{"sent":'.$pending.',"failed":0,"failedRecipients":[]}}',
            $response->getContent()
        );
    }

    /**
     * @return mixed[]
     */
    private function parseClickThrough(string $string): array
    {
        preg_match('/<a href=\"([^\"]*)\">(.*)<\/a>/iU', $string, $match);
        parse_str(parse_url($match[1], PHP_URL_QUERY), $queryParams);

        return unserialize(base64_decode($queryParams['ct']));
    }
}
