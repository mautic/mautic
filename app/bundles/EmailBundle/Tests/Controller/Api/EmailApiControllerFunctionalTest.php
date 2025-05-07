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
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

class EmailApiControllerFunctionalTest extends MauticMysqlTestCase
{
    private SmtpTransport $transport;

    protected function setUp(): void
    {
        $this->configParams['mailer_from_name']       = 'Mautic Admin';
        $this->configParams['default_signature_text'] = 'Best regards, |FROM_NAME|';
        parent::setUp();
        $this->loadFixtures([LoadCategoryData::class]);
        $this->setUpMailer();
    }

    private function setUpMailer(): void
    {
        $mailHelper = static::getContainer()->get('mautic.helper.mailer');
        $transport  = new SmtpTransport();
        $mailer     = new Mailer($transport);
        $this->setPrivateProperty($mailHelper, 'mailer', $mailer);
        $this->setPrivateProperty($mailHelper, 'transport', $transport);

        $this->transport  = $transport;
    }

    protected function beforeTearDown(): void
    {
        // Clear owners cache (to leave a clean environment for future tests):
        $mailHelper = static::getContainer()->get('mautic.helper.mailer');
        $this->setPrivateProperty($mailHelper, 'leadOwners', []);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    public function testCreateWithDynamicContent(): void
    {
        $segment = new LeadList();
        $segment->setName('API segment');
        $segment->setPublicName('API segment');
        $segment->setAlias('API segment');

        $this->em->persist($segment);
        $this->em->flush();

        $payload = [
            'name'           => 'test',
            'subject'        => 'API test email',
            'customHtml'     => '<h1>Hi there!</h1>',
            'emailType'      => 'list',
            'lists'          => [$segment->getId()],
            'dynamicContent' => [
                [
                    'tokenName' => 'test content name',
                    'content'   => 'Some default <strong>content</strong>',
                    'filters'   => [
                        [
                            'content' => 'Variation 1',
                            'filters' => [],
                        ],
                        [
                            'content' => 'Variation 2',
                            'filters' => [
                                [
                                    'glue'     => 'and',
                                    'field'    => 'city',
                                    'object'   => 'lead',
                                    'type'     => 'text',
                                    'filter'   => 'Prague',
                                    'display'  => null,
                                    'operator' => '=',
                                ],
                                [
                                    'glue'     => 'and',
                                    'field'    => 'email',
                                    'object'   => 'lead',
                                    'type'     => 'email',
                                    'filter'   => null, // Doesn't matter what value is here, it will be null-ed in the response for the emtpy param since PR 13526.
                                    'display'  => null,
                                    'operator' => '!empty',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'tokenName' => 'test content name2',
                    'content'   => 'Some default <strong>content2</strong>',
                    'filters'   => [
                        [
                            'content' => 'Variation 3',
                            'filters' => [],
                        ],
                        [
                            'content' => 'Variation 4',
                            'filters' => [
                                [
                                    'glue'     => 'and',
                                    'field'    => 'city',
                                    'object'   => 'lead',
                                    'type'     => 'text',
                                    'filter'   => 'Raleigh',
                                    'display'  => null,
                                    'operator' => '=',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/emails/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        Assert::assertArrayHasKey('email', $response);

        $response = $response['email'];

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertSame($payload['name'], $response['name']);
        Assert::assertSame($payload['subject'], $response['subject']);
        Assert::assertSame($payload['customHtml'], $response['customHtml']);
        Assert::assertSame($payload['lists'][0], $response['lists'][0]['id']);
        Assert::assertSame('API segment', $response['lists'][0]['name']);
        Assert::assertSame($payload['dynamicContent'], $response['dynamicContent']);
    }

    public function testSingleEmailWorkflow(): void
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
        $this->assertFalse($response['email']['publicPreview']);

        // Edit PATCH:
        $patchPayload = [
            'name'          => 'API email renamed',
            'lists'         => [$segmentBId],
            'publicPreview' => true,
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
        $this->assertEquals($patchPayload['publicPreview'], $response['email']['publicPreview']);

        // Edit PUT:
        $payload['subject'] .= ' renamed';
        $payload['lists']         = [$segmentAId, $segmentBId];
        $payload['language']      = 'en'; // Must be present for PUT as all empty values are being cleared.
        $payload['publicPreview'] = false; // Must be present for PUT as all empty values are being cleared.
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
        $this->assertEquals($payload['publicPreview'], $response['email']['publicPreview']);

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

    public function testReplyActionIfNotFound(): void
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
        $statRepository = static::getContainer()->get('mautic.email.repository.stat');

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
        $this->assertResponseIsSuccessful();
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

        $hasher = static::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);
        \assert($hasher instanceof PasswordHasherInterface);

        $user->setPassword($hasher->hash('password'));
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

        // Create an email:
        $createEmail = function () use ($segment) {
            $email = new Email();
            $email->setName('API email');
            $email->setSubject('Email created via API test');
            $email->setEmailType('list');
            $email->addList($segment);
            $email->setCustomHtml('<h1>Email content created by an API test</h1>{custom-token}<br>{signature}');
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
        $this->client->request('POST', "/api/emails/{$emailId}/send");
        $clientResponse = $this->client->getResponse();
        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals($sendResponse, ['success' => true, 'sentCount' => 1, 'failedRecipients' => 0], $clientResponse->getContent());

        $testEmail = function (string $customToken): void {
            $message = $this->transport->sentMessage;
            $this->assertSame($message->getSubject(), 'Email created via API test');
            $bodyRegExp = '#<h1>Email content created by an API test</h1>'.$customToken.'<br>Best regards, Mautic Admin<img height="1" width="1" src="[^"]+" alt="" />#';
            $this->assertMatchesRegularExpression($bodyRegExp, $message->getHtmlBody());
            $this->assertSame([$message->getTo()[0]->getAddress() => $message->getTo()[0]->getName()], ['jane@api.test' => 'Jane Doe']);
            $this->assertSame([$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()], ['from@api.test' => 'API Test']);
            $this->assertSame([$message->getReplyTo()[0]->getAddress() => $message->getReplyTo()[0]->getName()], ['reply@api.test' => '']);
            $this->assertSame([$message->getBcc()[0]->getAddress() => $message->getBcc()[0]->getName()], ['bcc@api.test' => '']);
        };
        $testEmail('{custom-token}');

        // Send to contact:
        $email = $createEmail();
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();
        $this->client->request('POST', "/api/emails/{$emailId}/contact/{$contactId}/send", ['tokens' => ['{custom-token}' => 'custom <b>value</b>']]);

        $clientResponse = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $sendResponse = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($sendResponse, ['success' => true], $clientResponse->getContent());
        $testEmail('custom <b>value</b>');

        // Test use owner as mailer:
        $email = $createEmail();
        $email->setUseOwnerAsMailer(true);
        $email->setReplyToAddress(null);
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();
        // Send to segment:
        $this->client->request('POST', "/api/emails/{$emailId}/send");
        $clientResponse = $this->client->getResponse();
        $sendResponse   = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals($sendResponse, ['success' => true, 'sentCount' => 1, 'failedRecipients' => 0], $clientResponse->getContent());

        $testEmailOwnerAsMailer = function (): void {
            $message = $this->transport->sentMessage;
            $this->assertSame($message->getSubject(), 'Email created via API test');
            $bodyRegExp = '#<h1>Email content created by an API test</h1>{custom-token}<br>Best regards, John Doe<img height="1" width="1" src="[^"]+" alt="" />#';
            $this->assertMatchesRegularExpression($bodyRegExp, $message->getHtmlBody());
            $this->assertSame([$message->getTo()[0]->getAddress() => $message->getTo()[0]->getName()], ['jane@api.test' => 'Jane Doe']);
            $this->assertSame([$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()], ['john@api.test' => 'John Doe']);
            $this->assertSame([$message->getReplyTo()[0]->getAddress() => $message->getReplyTo()[0]->getName()], ['john@api.test' => '']);
            $this->assertSame([$message->getBcc()[0]->getAddress() => $message->getBcc()[0]->getName()], ['bcc@api.test' => '']);
        };
        $testEmailOwnerAsMailer();

        // Send to contact:
        $email = $createEmail();
        $email->setUseOwnerAsMailer(true);
        $email->setReplyToAddress(null);
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();
        $this->client->request('POST', "/api/emails/{$emailId}/contact/{$contactId}/send");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $sendResponse = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($sendResponse, ['success' => true], $clientResponse->getContent());
        $testEmailOwnerAsMailer();

        // Test Custom Reply-To Address
        $email = $createEmail();
        $email->setUseOwnerAsMailer(true);
        $email->setReplyToAddress('reply@email.domain');
        $this->em->persist($email);
        $this->em->flush();
        $emailId = $email->getId();

        $this->client->request('POST', "/api/emails/{$emailId}/contact/{$contactId}/send");
        $clientResponse = $this->client->getResponse();

        $this->assertSame(200, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $sendResponse = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($sendResponse, ['success' => true], $clientResponse->getContent());

        $testCustomReplyTo = function (): void {
            $message = $this->transport->sentMessage;
            $this->assertSame($message->getSubject(), 'Email created via API test');
            $bodyRegExp = '#<h1>Email content created by an API test</h1>{custom-token}<br>Best regards, John Doe<img height="1" width="1" src="[^"]+" alt="" />#';
            $this->assertMatchesRegularExpression($bodyRegExp, $message->getHtmlBody());
            $this->assertSame([$message->getTo()[0]->getAddress() => $message->getTo()[0]->getName()], ['jane@api.test' => 'Jane Doe']);
            $this->assertSame([$message->getFrom()[0]->getAddress() => $message->getFrom()[0]->getName()], ['john@api.test' => 'John Doe']);
            $this->assertSame([$message->getReplyTo()[0]->getAddress() => $message->getReplyTo()[0]->getName()], ['reply@email.domain' => '']);
            $this->assertSame([$message->getBcc()[0]->getAddress() => $message->getBcc()[0]->getName()], ['bcc@api.test' => '']);
        };

        $testCustomReplyTo();
    }

    /**
     * @param mixed $value
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflector = new \ReflectionProperty($object::class, $property);
        $reflector->setAccessible(true);
        $reflector->setValue($object, $value);
    }

    public function testGetEmails(): void
    {
        $segment1 = $this->createSegment('Segment A', 'segment-a');
        $segment2 = $this->createSegment('Segment B', 'segment-b');
        $segment3 = $this->createSegment('Segment C', 'segment-c');
        $segment4 = $this->createSegment('Segment D', 'segment-d');
        $this->em->flush();
        $segments = [
            $segment1->getId() => $segment1,
            $segment2->getId() => $segment2,
            $segment3->getId() => $segment3,
            $segment4->getId() => $segment4,
        ];
        $email1   = $this->createEmail('Email A', 'Email A Subject', 'list', 'beefree-empty', 'Test html', $segments);
        $email2   = $this->createEmail('Email B', 'Email B Subject', 'list', 'beefree-empty', 'Test html', $segments);
        $email3   = $this->createEmail('Email C', 'Email C Subject', 'list', 'beefree-empty', 'Test html', $segments);
        $this->em->flush();

        $this->client->request('get', '/api/emails?limit=2');
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData['emails']);
        $this->assertSame([$email1->getId(), $email2->getId()], array_keys($responseData['emails']));

        $this->client->request('get', '/api/emails?limit=3');
        $response     = $this->client->getResponse();
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(3, $responseData['emails']);
        $this->assertSame([$email1->getId(), $email2->getId(), $email3->getId()], array_keys($responseData['emails']));
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

    /**
     * @param array<int, mixed> $segments
     *
     * @throws \Doctrine\ORM\ORMException
     */
    private function createEmail(string $name, string $subject, string $emailType, string $template, string $customHtml, array $segments = []): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject($subject);
        $email->setEmailType($emailType);
        $email->setTemplate($template);
        $email->setCustomHtml($customHtml);
        $email->setLists($segments);
        $this->em->persist($email);

        return $email;
    }
}
