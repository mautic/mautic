<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailSendFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['mailer_spool_type'] = 'file';
        parent::setUp();
    }

    public function testSendEmailWithContact(): void
    {
        $segment = $this->createSegment('Segment A', 'seg-a');
        $leads   = $this->createContacts(2, $segment);
        $content = '<!DOCTYPE html><htm><body><a href="https://localhost">link</a>
                        <a id="{unsubscribe_url}">unsubscribe here</a></body></html>';
        $email = $this->createEmail(
            'test subject',
            [$segment->getId() => $segment],
            $content
        );
        $this->em->flush();
        $this->em->clear();

        $this->client->request(
            Request::METHOD_POST,
            '/s/ajax?action=email:sendBatch',
            ['id' => $email->getId(), 'pending' => 2],
            [],
            $this->createAjaxHeaders()
        );

        $response = $this->client->getResponse();
        Assert::assertSame(Response::HTTP_OK, $response->getStatusCode(), $response->getContent());
        Assert::assertSame(
            '{"success":1,"percent":100,"progress":[2,2],"stats":{"sent":2,"failed":0,"failedRecipients":[]}}',
            $response->getContent()
        );

        $messages = $this->messageLogger->getMessages();
        foreach ($messages as $message) {
            preg_match('/<a href=\"([^\"]*)\">(.*)<\/a>/iU', $message->getBody(), $match);
            parse_str(parse_url($match[1], PHP_URL_QUERY), $queryParams);
            $clickThrough = unserialize(base64_decode($queryParams['ct']));
            Assert::assertSame($leads[array_key_first($message->getTo())]->getId(), $clickThrough['lead']);
        }
    }

    /**
     * @param array<string, LeadList> $segments
     */
    private function createEmail(string $subject, array $segments, string $emailContent): Email
    {
        $email = new Email();
        $email->setDateAdded(new DateTime());
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
     * @return array<string, LEAD>
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
