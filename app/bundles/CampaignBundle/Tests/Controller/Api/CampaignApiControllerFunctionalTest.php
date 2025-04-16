<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\UserEntityTrait;
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
use Symfony\Component\HttpFoundation\Response;

class CampaignApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use UserEntityTrait;

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

    public function testExportCampaignAction(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Create and persist campaign with events, as before
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

        // Create the campaign
        $campaign = new Campaign();
        $campaign->setName('test campaign');
        $campaign->setDescription('Test campaign for export');

        // Create events
        $event1 = new Event();
        $event1->setName('DWC event test');
        $event1->setDescription('API test');
        $event1->setType('dwc.decision');
        $event1->setEventType('decision'); // Set the event type
        $event1->setCampaign($campaign);  // Set the campaign for this event

        $event2 = new Event();
        $event2->setName('Send email');
        $event2->setDescription('API test');
        $event2->setType('email.send');
        $event2->setEventType('action'); // Set the event type
        $event2->setCampaign($campaign);  // Set the campaign for this event

        // Add events to the campaign (using addEvents)
        $campaign->addEvents([
            'new_43' => $event1, // Key for event1
            'new_44' => $event2, // Key for event2
        ]);

        // Persist campaign and events
        $this->em->persist($event1);
        $this->em->persist($event2);
        $this->em->persist($campaign);
        $this->em->flush();

        // Export the campaign
        $this->client->request(Request::METHOD_GET, '/api/campaigns/export/99999');
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(404, (string) $clientResponse->getStatusCode());

        $this->client->request(Request::METHOD_GET, '/api/campaigns/export/'.$campaign->getId());
        $clientResponse = $this->client->getResponse();

        // Check response status code
        $this->assertResponseStatusCodeSame(200, (string) $clientResponse->getStatusCode());

        // Decode the response content
        $responseData = json_decode($clientResponse->getContent(), true);

        // Ensure the response contains campaign data
        $this->assertNotEmpty($responseData);
        $this->assertArrayHasKey('campaign', $responseData[0]);

        // Since 'campaign' is an array, we'll need to check the first element
        $this->assertArrayHasKey('name', $responseData[0]['campaign'][0]);  // Access the first campaign in the array
        $this->assertEquals($campaign->getName(), $responseData[0]['campaign'][0]['name']);
        $this->assertEquals($campaign->getDescription(), $responseData[0]['campaign'][0]['description']);

        // Check if the campaign export includes the expected events
        $this->assertCount(2, $responseData[0]['campaign_event']);

        // Ensure proper serialization of the campaign events
        foreach ($responseData[0]['campaign_event'] as $event) {
            $this->assertArrayHasKey('id', $event);
            $this->assertArrayHasKey('name', $event);
            // Additional checks for event properties if necessary
        }
    }

    public function testExportCampaignActionAccessDenied(): void
    {
        // Create a user without export permissions
        $nonAdminUser = $this->createUserWithPermission([
            'user-name'  => 'non-admin',
            'email'      => 'non-admin@mautic-test.com',
            'first-name' => 'non-admin',
            'last-name'  => 'non-admin',
            'role'       => [
                'name'        => 'perm_non_admin',
                'permissions' => [
                    'campaign:campaigns'     => 2,
                    'campaign:export:enable' => 2,
                ],
            ],
        ]);

        $this->loginUser($nonAdminUser);

        // Create and persist a campaign
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');
        $campaign->setDescription('Test description');
        $this->em->persist($campaign);
        $this->em->flush();

        // Attempt to export the campaign
        $this->client->request(Request::METHOD_GET, '/api/campaigns/export/'.$campaign->getId());

        $response = $this->client->getResponse();

        // Assert that access is denied
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testImportCampaignActionJson(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Provide the full structure exactly like the export output
        $payload = [[
            'campaign' => [[
                'id'              => 1,
                'name'            => 'test2',
                'description'     => null,
                'is_published'    => false,
                'canvas_settings' => [
                    'nodes' => [
                        ['id' => '1', 'positionX' => '553', 'positionY' => '158'],
                        ['id' => 'lists', 'positionX' => '653', 'positionY' => '53'],
                    ],
                    'connections' => [
                        [
                            'sourceId' => 'lists',
                            'targetId' => '1',
                            'anchors'  => [
                                'source' => 'leadsource',
                                'target' => 'top',
                            ],
                        ],
                    ],
                ],
                'uuid' => 'b4ddc4d7-149e-4a81-9141-0e03c598627a',
            ]],
            'campaign_event' => [[
                'id'          => 1,
                'campaign_id' => 1,
                'name'        => 'Device visit',
                'description' => null,
                'type'        => 'page.devicehit',
                'event_type'  => 'decision',
                'event_order' => 0,
                'properties'  => [
                    'device_type'  => [],
                    'device_brand' => [],
                    'device_os'    => [],
                ],
                'trigger_interval'      => 0,
                'trigger_interval_unit' => null,
                'trigger_mode'          => null,
                'triggerDate'           => null,
                'channel'               => 'page',
                'channel_id'            => 0,
                'parent_id'             => null,
                'uuid'                  => 'b3c03e30-d6a2-469b-9607-a9a98d7ef238',
            ]],
            'lists' => [[
                'id'                   => 1,
                'name'                 => 'Test Seg',
                'is_published'         => true,
                'description'          => null,
                'alias'                => 'test-seg',
                'public_name'          => 'Test Seg',
                'filters'              => [],
                'is_global'            => true,
                'is_preference_center' => false,
                'uuid'                 => 'd697157e-9ae3-4600-aa2e-4a2a5a6e36e0',
            ]],
            'dependencies' => [[
                'campaign_event' => [
                    ['campaign' => 1, 'campaign_event' => 1],
                ],
                'lists' => [
                    ['campaign' => 1, 'lists' => 1],
                ],
            ]],
        ]];

        $this->client->request(
            Request::METHOD_POST,
            '/api/campaigns/import',
            [],
            [],
            [],
            json_encode($payload, JSON_PRETTY_PRINT)
        );

        $clientResponse = $this->client->getResponse();

        // Debug early exit if something fails
        if (201 !== $clientResponse->getStatusCode()) {
            $this->fail('Import failed with error: '.$clientResponse->getContent());
        }

        // Success check
        $this->assertResponseStatusCodeSame(201, 'Expected status code 201 for successful import.');
        $responseData = json_decode($clientResponse->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertContains('Campaign imported successfully.', $responseData);
    }

    public function testImportCampaignActionZip(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Same campaign structure as the export
        $payload = [[
            'campaign' => [[
                'id'              => 1,
                'name'            => 'test zip import',
                'description'     => 'Imported from ZIP',
                'is_published'    => false,
                'canvas_settings' => [
                    'nodes' => [
                        ['id' => '1', 'positionX' => '100', 'positionY' => '100'],
                        ['id' => 'lists', 'positionX' => '200', 'positionY' => '50'],
                    ],
                    'connections' => [
                        [
                            'sourceId' => 'lists',
                            'targetId' => '1',
                            'anchors'  => [
                                'source' => 'leadsource',
                                'target' => 'top',
                            ],
                        ],
                    ],
                ],
                'uuid' => 'zip-uuid-test',
            ]],
            'campaign_event' => [[
                'id'               => 1,
                'campaign_id'      => 1,
                'name'             => 'Event via ZIP',
                'description'      => null,
                'type'             => 'page.devicehit',
                'event_type'       => 'decision',
                'event_order'      => 0,
                'properties'       => [],
                'trigger_interval' => 0,
                'channel'          => 'page',
                'channel_id'       => 0,
                'uuid'             => 'event-uuid-zip',
            ]],
            'lists' => [[
                'id'                   => 1,
                'name'                 => 'ZIP Seg',
                'alias'                => 'zip-seg',
                'public_name'          => 'ZIP Seg',
                'is_published'         => true,
                'filters'              => [],
                'uuid'                 => 'list-uuid-zip',
                'is_global'            => true,
                'is_preference_center' => false,
            ]],
            'dependencies' => [[
                'campaign_event' => [
                    ['campaign' => 1, 'campaign_event' => 1],
                ],
                'lists' => [
                    ['campaign' => 1, 'lists' => 1],
                ],
            ]],
        ]];

        // Create temporary zip file
        $zip     = new \ZipArchive();
        $zipPath = tempnam(sys_get_temp_dir(), 'mautic_zip_test').'.zip';

        if (true === $zip->open($zipPath, \ZipArchive::CREATE)) {
            $zip->addFromString('campaign.json', json_encode($payload, JSON_PRETTY_PRINT));
            $zip->close();
        } else {
            $this->fail('Failed to create test ZIP file.');
        }

        // Upload via API
        $this->client->request(
            Request::METHOD_POST,
            '/api/campaigns/import',
            [],
            ['file'         => new \Symfony\Component\HttpFoundation\File\UploadedFile($zipPath, 'import.zip')],
            ['CONTENT_TYPE' => 'multipart/form-data']
        );

        $response = $this->client->getResponse();

        // Clean up file
        unlink($zipPath);

        if (201 !== $response->getStatusCode()) {
            $this->fail('Import failed with error: '.$response->getContent());
        }

        $this->assertResponseStatusCodeSame(201);
        $decoded = json_decode($response->getContent(), true);
        $this->assertContains('Campaign imported successfully.', $decoded);
    }

    public function testImportCampaignAccessDenied(): void
    {
        $userWithoutPermission = $this->createUserWithPermission([
            'user-name'  => 'no-import-user',
            'email'      => 'no-import@mautic-test.com',
            'first-name' => 'NoImport',
            'last-name'  => 'User',
            'role'       => [
                'name'        => 'no_import_role',
                'permissions' => [
                    // Do not grant 'campaign:imports:create'
                ],
            ],
        ]);

        $this->loginUser($userWithoutPermission);

        // Attempt to import a campaign
        $this->client->request(Request::METHOD_POST, '/api/campaigns/import');

        $response = $this->client->getResponse();

        // Assert that access is denied
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testImportCampaignNoFileUploaded(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Attempt to import with no files
        $this->client->request(Request::METHOD_POST, '/api/campaigns/import');

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('No JSON content found and exactly one ZIP file must be uploaded.', $response->getContent());
    }

    private function createTemporaryFile(string $extension): string
    {
        $filePath = tempnam(sys_get_temp_dir(), 'mautic_test_').'.'.$extension;
        file_put_contents($filePath, 'test content');

        return $filePath;
    }

    public function testImportCampaignInvalidFile(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Create a temporary file
        $filePath = $this->createTemporaryFile('txt');

        // Upload the invalid file
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile($filePath, 'test.txt', null, null, true);

        $this->client->request(Request::METHOD_POST, '/api/campaigns/import', [], ['file' => $file]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Unsupported file type. Only ZIP archives are supported.', $response->getContent());

        // Clean up
        unlink($filePath);
    }

    public function testImportCampaignUnsupportedFileType(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Create a temporary file with a non-ZIP extension
        $filePath = $this->createTemporaryFile('txt');
        $file     = new \Symfony\Component\HttpFoundation\File\UploadedFile($filePath, 'test.txt', null, null, true);

        $this->client->request(Request::METHOD_POST, '/api/campaigns/import', [], ['file' => $file]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Unsupported file type. Only ZIP archives are supported.', $response->getContent());

        // Clean up
        unlink($filePath);
    }

    public function testImportCampaignFilePathDoesNotExist(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        $filePath = tempnam(sys_get_temp_dir(), 'mautic_test_');

        // Create an UploadedFile object with the deleted file path
        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile($filePath, 'test.zip', null, null, true);

        $this->client->request(Request::METHOD_POST, '/api/campaigns/import', [], ['file' => $file]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testImportCampaignMalformedJson(): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['username' => 'admin']);
        $this->loginUser($user);

        // Create a temporary ZIP file with invalid JSON
        $zipPath = $this->createTemporaryFile('zip');
        $zip     = new \ZipArchive();
        if (true === $zip->open($zipPath, \ZipArchive::CREATE)) {
            $zip->addFromString('malformed.json', '{invalid json}');
            $zip->close();
        }

        $file = new \Symfony\Component\HttpFoundation\File\UploadedFile($zipPath, 'test.zip', null, null, true);

        $this->client->request(Request::METHOD_POST, '/api/campaigns/import', [], ['file' => $file]);

        $response = $this->client->getResponse();

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertStringContainsString('Malformed campaign JSON file - unable to parse JSON.', $response->getContent());

        // Clean up
        unlink($zipPath);
    }
}
