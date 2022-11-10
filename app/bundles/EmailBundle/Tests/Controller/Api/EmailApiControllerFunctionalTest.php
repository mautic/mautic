<?php

namespace Mautic\EmailBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Tests\Helper\Transport\SmtpTransport;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Swift_Mailer;
use Symfony\Component\HttpFoundation\Response;

class EmailApiControllerFunctionalTest extends MauticMysqlTestCase
{
    private SmtpTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCategoryData::class]);
        $this->setUpMailer();
    }

    private function setUpMailer(): void
    {
        $mailHelper = self::$container->get('mautic.helper.mailer');
        $transport  = new SmtpTransport();
        $mailer     = new Swift_Mailer($transport);
        $this->setPrivateProperty($mailHelper, 'mailer', $mailer);
        $this->setPrivateProperty($mailHelper, 'transport', $transport);

        $this->transport  = $transport;
    }

    protected function tearDown(): void
    {
        // Clear owners cache (to leave a clean environment for future tests):
        $mailHelper = self::$container->get('mautic.helper.mailer');
        $this->setPrivateProperty($mailHelper, 'leadOwners', []);

        parent::tearDown();
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    public function testSingleEmailWorkflow()
    {
        // Create a couple of segments first:
        $payload = [
            [
                'name'        => 'API segment A',
                'description' => 'Segment created via API test',
            ],
            [
                'name'        => 'API segment B',
                'description' => 'Segment created via API test',
            ],
        ];

        $this->client->request('POST', '/api/segments/batch/new', $payload);
        $clientResponse  = $this->client->getResponse();
        $segmentResponse = json_decode($clientResponse->getContent(), true);
        $segmentAId      = $segmentResponse['lists'][0]['id'];
        $segmentBId      = $segmentResponse['lists'][1]['id'];

        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertGreaterThan(0, $segmentAId);

        // Create email with the new segment:
        $payload = [
            'name'       => 'API email',
            'subject'    => 'Email created via API test',
            'emailType'  => 'list',
            'lists'      => [$segmentAId],
            'customHtml' => '<h1>Email content created by an API test</h1>',
        ];

        $this->client->request('POST', '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $emailId        = $response['email']['id'];

        $this->assertSame(201, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertGreaterThan(0, $emailId);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertCount(1, $response['email']['lists']);
        $this->assertEquals($segmentAId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Edit PATCH:
        $patchPayload = [
            'name'  => 'API email renamed',
            'lists' => [$segmentBId],
        ];
        $this->client->request('PATCH', "/api/emails/{$emailId}/edit", $patchPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals('API email renamed', $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(1, $response['email']['lists']);
        $this->assertEquals($segmentBId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Edit PUT:
        $payload['subject'] .= ' renamed';
        $payload['lists']    = [$segmentAId, $segmentBId];
        $payload['language'] = 'en'; // Must be present for PUT as all empty values are being cleared.
        $this->client->request('PUT', "/api/emails/{$emailId}/edit", $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals('Email created via API test renamed', $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($segmentAId, $response['email']['lists'][1]['id']);
        $this->assertEquals($segmentBId, $response['email']['lists'][0]['id']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Get:
        $this->client->request('GET', "/api/emails/{$emailId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame($emailId, $response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Delete:
        $this->client->request('DELETE', "/api/emails/{$emailId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertNull($response['email']['id']);
        $this->assertEquals($payload['name'], $response['email']['name']);
        $this->assertEquals($payload['subject'], $response['email']['subject']);
        $this->assertCount(2, $response['email']['lists']);
        $this->assertEquals($payload['emailType'], $response['email']['emailType']);
        $this->assertEquals($payload['customHtml'], $response['email']['customHtml']);

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/emails/{$emailId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertSame(404, $response['errors'][0]['code']);

        // Delete also testing segments:
        $this->client->request('DELETE', "/api/segments/batch/delete?ids={$segmentAId},{$segmentBId}", []);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Response should include the two entities that we just deleted
        $this->assertSame(2, count($response['lists']));
        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
    }

    public function testReplyActionIfNotFound()
    {
        $trackingHash = 'tracking_hash_123';

        // Create new email reply.
        $this->client->request('POST', "/api/emails/reply/{$trackingHash}");
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame('Email Stat with tracking hash tracking_hash_123 was not found', $responseData['errors'][0]['message']);
    }

    public function testReplyAction(): void
    {
        $trackingHash = 'tracking_hash_123';

        /** @var StatRepository $statRepository */
        $statRepository = self::$container->get('mautic.email.repository.stat');

        // Create a test email stat.
        $stat = new Stat();
        $stat->setTrackingHash($trackingHash);
        $stat->setEmailAddress('john@doe.email');
        $stat->setDateSent(new \DateTime());

        $statRepository->saveEntity($stat);

        // Create new email reply.
        $this->client->request('POST', "/api/emails/reply/{$trackingHash}");
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame(['success' => true], json_decode($response->getContent(), true));

        // Get the email reply that was just created from the stat API.
        $statReplyQuery = ['where' => [['col' => 'stat_id', 'expr' => 'eq', 'val' => $stat->getId()]]];
        $this->client->request('GET', '/api/stats/email_stat_replies', $statReplyQuery);
        $fetchedReplyData = json_decode($this->client->getResponse()->getContent(), true);

        // Check that the email reply was created correctly.
        $this->assertSame('1', $fetchedReplyData['total']);
        $this->assertSame($stat->getId(), $fetchedReplyData['stats'][0]['stat_id']);
        $this->assertMatchesRegularExpression('/api-[a-z0-9]*/', $fetchedReplyData['stats'][0]['message_id']);

        // Get the email stat that was just updated from the stat API.
        $statQuery = ['where' => [['col' => 'id', 'expr' => 'eq', 'val' => $stat->getId()]]];
        $this->client->request('GET', '/api/stats/email_stats', $statQuery);
        $fetchedStatData = json_decode($this->client->getResponse()->getContent(), true);

        // Check that the email stat was updated correctly/
        $this->assertSame('1', $fetchedStatData['total']);
        $this->assertSame($stat->getId(), $fetchedStatData['stats'][0]['id']);
        $this->assertSame('1', $fetchedStatData['stats'][0]['is_read']);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}/', $fetchedStatData['stats'][0]['date_read']);
    }

    public function testSendAction(): void
    {
        // Create a user (to test use onwer as mailer):
        $role = new Role();
        $role->setName('Role');
        $this->em->persist($role);

        $user = new User();
        $user->setUserName('apitest');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setEmail('john@api.test');
        $user->setSignature('Best regards, |FROM_NAME|');
        $user->setRole($role);
        $encoder = self::$container->get('security.encoder_factory')->getEncoder($user);
        $user->setPassword($encoder->encodePassword('password', null));
        $this->em->persist($user);

        // Create a contact:
        $contact = new Lead();
        $contact->setFirstName('Jane');
        $contact->setLastName('Doe');
        $contact->setEmail('jane@api.test');
        $contact->setOwner($user);
        $this->em->persist($contact);

        // Create a segment:
        $segment = new LeadList();
        $segment->setName('API segment');
        $segment->setPublicName('API segment');
        $segment->setAlias('API segment');
        $segment->setDescription('Segment created via API test');
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        // Add contact to segment:
        $segmentContact = new ListLead();
        $segmentContact->setLead($contact);
        $segmentContact->setList($segment);
        $segmentContact->setDateAdded(new \DateTime());
        $this->em->persist($segmentContact);

        // Commit
        $this->em->flush();

        $contactId = $contact->getId();
        $segmentId = $segment->getId();

        // Create an email:
        $createEmail = function () use ($segment) {
            $email = new Email();
            $email->setName('API email');
            $email->setSubject('Email created via API test');
            $email->setEmailType('list');
            $email->addList($segment);
            $email->setCustomHtml('<h1>Email content created by an API test</h1><br>{signature}');
            $email->setIsPublished(true);
            $email->setFromAddress('from@api.test');
            $email->setFromName('API Test');
            $email->setReplyToAddress('reply@api.test');
            $email->setBccAddress('bcc@api.test');

            return $email;
        };

        $email = $createEmail();
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();

        // Send to segment:
        $this->client->request('POST', "/api/emails/${emailId}/send");
        $clientResponse = $this->client->getResponse();
        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals($sendResponse, ['success' => true, 'sentCount' => 1, 'failedRecipients' => 0], $clientResponse->getContent());

        $testEmail = function (): void {
            $message = $this->transport->sentMessage;
            $this->assertSame($message->getSubject(), 'Email created via API test');
            $bodyRegExp = '#<h1>Email content created by an API test</h1><br><img height="1" width="1" src="[^"]+" alt="" />#';
            $this->assertMatchesRegularExpression($bodyRegExp, $message->getBody());
            $this->assertSame($message->getTo(), ['jane@api.test' => 'Jane Doe']);
            $this->assertSame($message->getFrom(), ['from@api.test' => 'API Test']);
            $this->assertSame($message->getReplyTo(), ['reply@api.test' => null]);
            $this->assertSame($message->getBcc(), ['bcc@api.test' => null]);
        };
        $testEmail();

        // Send to contact:
        $this->client->request('POST', "/api/emails/${emailId}/contact/${contactId}/send");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($sendResponse, ['success' => true], $clientResponse->getContent());
        $testEmail();

        // Test use owner as mailer:
        $email = $createEmail();
        $email->setUseOwnerAsMailer(true);
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();

        // Send to segment:
        $this->client->request('POST', "/api/emails/${emailId}/send");
        $clientResponse = $this->client->getResponse();
        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals($sendResponse, ['success' => true, 'sentCount' => 1, 'failedRecipients' => 0], $clientResponse->getContent());

        $testEmailOwnerAsMailer = function (): void {
            $message = $this->transport->sentMessage;
            $this->assertSame($message->getSubject(), 'Email created via API test');
            $bodyRegExp = '#<h1>Email content created by an API test</h1><br>Best regards, John Doe<img height="1" width="1" src="[^"]+" alt="" />#';
            $this->assertMatchesRegularExpression($bodyRegExp, $message->getBody());
            $this->assertSame($message->getTo(), ['jane@api.test' => 'Jane Doe']);
            $this->assertSame($message->getFrom(), ['john@api.test' => 'John Doe']);
            $this->assertSame($message->getReplyTo(), ['john@api.test' => null]);
            $this->assertSame($message->getBcc(), ['bcc@api.test' => null]);
        };
        $testEmailOwnerAsMailer();

        // Send to contact:
        $this->client->request('POST', "/api/emails/${emailId}/contact/${contactId}/send");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($sendResponse, ['success' => true], $clientResponse->getContent());
        $testEmailOwnerAsMailer();
    }

    /**
     * @param mixed $value
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflector = new \ReflectionProperty(get_class($object), $property);
        $reflector->setAccessible(true);
        $reflector->setValue($object, $value);
    }
}
