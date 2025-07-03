<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class CampaignApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        $this->configParams['mailer_from_name']  = 'Mautic Admin';
        $this->configParams['mailer_from_email'] = 'admin@email.com';

        parent::setUp();
    }

    public function testCreateNewCampaign(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $segment = new LeadList();
        $segment->setName('test');
        $segment->setAlias('test');
        $segment->setPublicName('test');

        $email = new Email();
        $email->setName('test');
        $email->setSubject('Ahoy {contactfield=email}');
        $email->setCustomHtml('Your email is <b>{contactfield=email}</b>');
        $email->setUseOwnerAsMailer(true);

        $dwc = new DynamicContent();
        $dwc->setName('test');
        $dwc->setSlotName('test');
        $dwc->setContent('test');

        $company = new Company();
        $company->setName('test');

        $contact1 = new Lead();
        $contact1->setEmail('contact@one.email');

        $contact2 = new Lead();
        $contact2->setEmail('contact@two.email');
        $contact2->setOwner($user);

        $member1 = new ListLead();
        $member1->setLead($contact1);
        $member1->setList($segment);
        $member1->setDateAdded(new \DateTime());

        $member2 = new ListLead();
        $member2->setLead($contact2);
        $member2->setList($segment);
        $member2->setDateAdded(new \DateTime());

        $this->em->persist($segment);
        $this->em->persist($email);
        $this->em->persist($dwc);
        $this->em->persist($company);
        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->persist($member1);
        $this->em->persist($member2);
        $this->em->flush();

        $payload = [
            'name'        => 'test',
            'description' => 'Created via API',
            'events'      => [
                [
                    'id'          => 'new_43', // Event ID will be replaced on /new
                    'name'        => 'DWC event test',
                    'description' => 'API test',
                    'type'        => 'dwc.decision',
                    'eventType'   => 'decision',
                    'order'       => 1,
                    'properties'  => [
                        'dwc_slot_name'  => 'test',
                        'dynamicContent' => $dwc->getId(),
                    ],
                    'triggerInterval'     => 0,
                    'triggerIntervalUnit' => null,
                    'triggerMode'         => null,
                    'children'            => [
                        'new_55', // Event ID will be replaced on /new
                    ],
                    'parent'       => null,
                    'decisionPath' => null,
                ],
                [
                    'id'          => 'new_44', // Event ID will be replaced on /new
                    'name'        => 'Send email',
                    'description' => 'API test',
                    'type'        => 'email.send',
                    'eventType'   => 'action',
                    'order'       => 2,
                    'properties'  => [
                        'email'      => $email->getId(),
                        'email_type' => MailHelper::EMAIL_TYPE_TRANSACTIONAL,
                    ],
                    'triggerInterval'     => 0,
                    'triggerIntervalUnit' => 'd',
                    'triggerMode'         => 'interval',
                    'children'            => [],
                    'parent'              => null,
                    'decisionPath'        => 'yes',
                ],
                [
                    'id'          => 'new_55', // Event ID will be replaced on /new
                    'name'        => 'Add to company action',
                    'description' => 'API test',
                    'type'        => 'lead.addtocompany',
                    'eventType'   => 'action',
                    'order'       => 2,
                    'properties'  => [
                        'company' => $company->getId(),
                    ],
                    'triggerInterval'     => 1,
                    'triggerIntervalUnit' => 'd',
                    'triggerMode'         => 'interval',
                    'children'            => [],
                    'parent'              => 'new_43', // Event ID will be replaced on /new
                    'decisionPath'        => 'no',
                ],
            ],
            'forms' => [],
            'lists' => [
                [
                    'id' => $segment->getId(),
                ],
            ],
            'canvasSettings' => [
                'nodes' => [
                    [
                        'id'        => 'new_43', // Event ID will be replaced on /new
                        'positionX' => '650',
                        'positionY' => '189',
                    ],
                    [
                        'id'        => 'new_44', // Event ID will be replaced on /new
                        'positionX' => '433',
                        'positionY' => '348',
                    ],
                    [
                        'id'        => 'new_55', // Event ID will be replaced on /new
                        'positionX' => '750',
                        'positionY' => '411',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '629',
                        'positionY' => '65',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_43', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'lists',
                        'targetId' => 'new_44', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                    [
                        'sourceId' => 'new_43', // Event ID will be replaced on /new
                        'targetId' => 'new_55', // Event ID will be replaced on /new
                        'anchors'  => [
                            'source' => 'no',
                            'target' => 'top',
                        ],
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_POST, 'api/campaigns/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(201, $clientResponse->getContent());
        $response   = json_decode($clientResponse->getContent(), true);
        $campaignId = $response['campaign']['id'];
        Assert::assertGreaterThan(0, $campaignId);
        Assert::assertEquals($payload['name'], $response['campaign']['name']);
        Assert::assertEquals($payload['description'], $response['campaign']['description']);
        Assert::assertEquals($payload['events'][0]['name'], $response['campaign']['events'][0]['name']);
        Assert::assertEquals($segment->getId(), $response['campaign']['lists'][0]['id']);

        $commandTester = $this->testSymfonyCommand('mautic:campaigns:update', ['-i' => $campaignId]);
        $commandTester->assertCommandIsSuccessful();
        Assert::assertStringContainsString('2 total contact(s) to be added', $commandTester->getDisplay());
        Assert::assertStringContainsString('100%', $commandTester->getDisplay());

        $commandTester = $this->testSymfonyCommand('mautic:campaigns:trigger', ['-i' => $campaignId]);
        $commandTester->assertCommandIsSuccessful();
        // 2 events were executed for each of the 2 contacts (= 4). The third event is waiting for the decision interval.
        Assert::assertStringContainsString('4 total events were executed', $commandTester->getDisplay());

        $this->assertQueuedEmailCount(2);

        $email1 = $this->getMailerMessagesByToAddress('contact@one.email')[0];

        // The email is has mailer is owner ON but this contact doesn't have any owner. So it uses default FROM and Reply-To.
        Assert::assertSame('Ahoy contact@one.email', $email1->getSubject());
        Assert::assertMatchesRegularExpression('#Your email is <b>contact@one\.email<\/b><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email1->getHtmlBody());
        Assert::assertSame('Your email is contact@one.email', $email1->getTextBody());
        Assert::assertCount(1, $email1->getFrom());
        Assert::assertSame($this->configParams['mailer_from_name'], $email1->getFrom()[0]->getName());
        Assert::assertSame($this->configParams['mailer_from_email'], $email1->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email1->getTo());
        Assert::assertSame('', $email1->getTo()[0]->getName());
        Assert::assertSame($contact1->getEmail(), $email1->getTo()[0]->getAddress());
        Assert::assertCount(1, $email1->getReplyTo());
        Assert::assertSame('', $email1->getReplyTo()[0]->getName());
        Assert::assertSame($this->configParams['mailer_from_email'], $email1->getReplyTo()[0]->getAddress());

        $email2 = $this->getMailerMessagesByToAddress('contact@two.email')[0];

        // This contact does have an owner so it uses FROM and Rply-to from the owner.
        Assert::assertSame('Ahoy contact@two.email', $email2->getSubject());
        Assert::assertMatchesRegularExpression('#Your email is <b>contact@two\.email<\/b><img height="1" width="1" src="https:\/\/localhost\/email\/[a-z0-9]+\.gif" alt="" \/>#', $email2->getHtmlBody());
        Assert::assertSame('Your email is contact@two.email', $email2->getTextBody());
        Assert::assertCount(1, $email2->getFrom());
        Assert::assertSame($user->getName(), $email2->getFrom()[0]->getName());
        Assert::assertSame($user->getEmail(), $email2->getFrom()[0]->getAddress());
        Assert::assertCount(1, $email2->getTo());
        Assert::assertSame('', $email2->getTo()[0]->getName());
        Assert::assertSame($contact2->getEmail(), $email2->getTo()[0]->getAddress());
        Assert::assertCount(1, $email2->getReplyTo());
        Assert::assertSame('', $email2->getReplyTo()[0]->getName());
        Assert::assertSame($user->getEmail(), $email2->getReplyTo()[0]->getAddress());

        // Search for this campaign:
        $this->client->request(Request::METHOD_GET, "/api/campaigns?search=ids:{$response['campaign']['id']}");
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful($clientResponse->getContent());
        $response = json_decode($clientResponse->getContent(), true);
        Assert::assertEquals($payload['name'], $response['campaigns'][$campaignId]['name'], $clientResponse->getContent());
        Assert::assertEquals($payload['description'], $response['campaigns'][$campaignId]['description'], $clientResponse->getContent());
        Assert::assertEquals($payload['events'][0]['name'], $response['campaigns'][$campaignId]['events'][0]['name'], $clientResponse->getContent());
        Assert::assertEquals($segment->getId(), $response['campaigns'][$campaignId]['lists'][0]['id'], $clientResponse->getContent());
    }
}
