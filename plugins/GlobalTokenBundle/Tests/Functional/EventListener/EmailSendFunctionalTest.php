<?php

declare(strict_types=1);

namespace MauticPlugin\GlobalTokenBundle\Tests\Functional\EventListener;

use DateTime;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use MauticPlugin\GlobalTokenBundle\Entity\GlobalToken;
use MauticPlugin\GlobalTokenBundle\GlobalToken\TypeList;
use MauticPlugin\GlobalTokenBundle\Provider\ConfigProvider;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailSendFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams[ConfigProvider::PLUGIN_ENABLED] = true;
        $this->configParams['mailer_spool_type']            = 'file';
        parent::setUp();
    }

    public function testSendEmailWithGlobalTokenContact(): void
    {
        $globalToken = $this->createGlobalTokenByKey(1);
        $segment     = $this->createSegment('Segment A', 'seg-a');
        $leads       = $this->createContacts(2, $segment);
        $content     = '<!DOCTYPE html><htm><body><a href="https://localhost">link</a></body></html>';
        $email       = $this->createEmail(
            "{global-token={$globalToken->getAlias()}}",
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
            Assert::assertSame($globalToken->getContent(), $message->getSubject());
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

    private function createGlobalTokenByKey(int $key): GlobalToken
    {
        $globalToken = new GlobalToken();
        $globalToken->setName("Test Token {$key}");
        $globalToken->setAlias("test-token-{$key}");
        $globalToken->setType(TypeList::TEXT);
        $globalToken->setContent('test-token-content-'.$key);
        $globalToken->setDateAdded((new \DateTime('now'))->add(new \DateInterval("PT{$key}S")));
        $this->em->persist($globalToken);

        return $globalToken;
    }
}
