<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\AssetBundle\Entity\Download;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Test\Session\FixedMockFileSessionStorage;
use Mautic\DynamicContentBundle\Entity\Stat as StatDC;
use Mautic\EmailBundle\Entity\Stat as StatEmail;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class LeadApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled'] = 'testDisabledApi' !== $this->getName();

        static::getContainer()->set(
            'session',
            new Session(
                new class extends FixedMockFileSessionStorage {
                    public function start(): bool
                    {
                        throw new \RuntimeException('Session cannot be started during API call. It must be stateless.');
                    }
                }
            )
        );

        parent::setUp();
    }

    public function testDisabledApi(): void
    {
        $this->client->request('POST', '/api/contacts/new', ['email' => 'apiemail1@email.com']);
        $clientResponse = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_FORBIDDEN, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals(
            '{"errors":[{"message":"API disabled. You need to enable the API in the API settings of Mautic\u0027s Configuration.","code":403,"type":"api_disabled"}]}',
            $clientResponse->getContent()
        );
    }

    public function testActivityApi(): void
    {
        $this->client->request('GET', '/api/contacts/activity');
        Assert::assertTrue($this->client->getResponse()->isOk(), $this->client->getResponse()->getContent());
        Assert::assertArrayHasKey('events', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('filters', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('order', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('types', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('total', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('page', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('limit', json_decode($this->client->getResponse()->getContent(), true));
        Assert::assertArrayHasKey('maxPages', json_decode($this->client->getResponse()->getContent(), true));
    }

    public function testBatchNewEndpointDoesNotCreateDuplicates(): void
    {
        $companyA = $this->createCompany('CompanyA corp');
        $companyB = $this->createCompany('CompanyB corp');
        $payload  = [
            [
                'email'            => 'batchemail1@email.com',
                'firstname'        => 'BatchUpdate',
                'points'           => 4,
                'tags'             => ['batchtest', 'testbatch'],
                'city'             => 'Houston',
                'state'            => 'Texas',
                'country'          => 'United States',
                'preferred_locale' => 'es_SV',
                'timezone'         => 'America/Chicago',
                'owner'            => 1,
                'company'          => $companyA->getId(),
            ],
            [
                'email'            => 'batchemail2@email.com',
                'firstname'        => 'BatchUpdate2',
                'tags'             => ['batchtest', 'testbatch', 'batchremovetest'],
                'city'             => 'Boston',
                'state'            => 'Massachusetts',
                'country'          => 'United States',
                'preferred_locale' => 'en_GB',
                'timezone'         => 'America/New_York',
            ],
            [
                'email'     => 'batchemail3@email.com',
                'firstname' => 'BatchUpdate3',
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        // Assert status codes
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $contactId1 = $response['contacts'][0]['id'];
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1]);
        $contactId2 = $response['contacts'][1]['id'];
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2]);
        $contactId3 = $response['contacts'][2]['id'];

        // Assert email
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[1]['email'], $response['contacts'][1]['fields']['all']['email']);
        $this->assertEquals($payload[2]['email'], $response['contacts'][2]['fields']['all']['email']);

        // Assert firstname
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($payload[1]['firstname'], $response['contacts'][1]['fields']['all']['firstname']);
        $this->assertEquals($payload[2]['firstname'], $response['contacts'][2]['fields']['all']['firstname']);

        // Assert points while also testing empty precision as points is treated as a custom field
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertSame(0, $response['contacts'][1]['points']);
        $this->assertSame(0, $response['contacts'][2]['points']);
        $this->assertSame(0, $response['contacts'][2]['fields']['all']['points']);

        // Assert tags
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals(3, count($response['contacts'][1]['tags']));
        $this->assertEquals(0, count($response['contacts'][2]['tags']));

        // Assert city
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[1]['city'], $response['contacts'][1]['fields']['all']['city']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['city']);

        // Assert state
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[1]['state'], $response['contacts'][1]['fields']['all']['state']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['state']);

        // Assert country
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[1]['country'], $response['contacts'][1]['fields']['all']['country']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['country']);

        // Assert preferred_locale
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[1]['preferred_locale'], $response['contacts'][1]['fields']['all']['preferred_locale']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['preferred_locale']);

        // Assert timezone
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[1]['timezone'], $response['contacts'][1]['fields']['all']['timezone']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['timezone']);

        // Assert owner
        $this->assertEquals($payload[0]['owner'], $response['contacts'][0]['owner']['id']);
        $this->assertEquals(null, $response['contacts'][1]['owner']);
        $this->assertEquals(null, $response['contacts'][2]['owner']);

        // Assert company
        $this->assertEquals($companyA->getId(), (int) $response['contacts'][0]['fields']['all']['company']);
        $this->assertEquals(null, $response['contacts'][1]['fields']['all']['company']);
        $this->assertEquals(null, $response['contacts'][2]['fields']['all']['company']);

        // Assert date modified
        $this->assertNotEmpty($response['contacts'][0]['dateModified']);
        $this->assertNotEmpty($response['contacts'][1]['dateModified']);
        $this->assertNotEmpty($response['contacts'][2]['dateModified']);

        // Assert date added
        $this->assertNotEmpty($response['contacts'][0]['dateAdded']);
        $this->assertNotEmpty($response['contacts'][1]['dateAdded']);
        $this->assertNotEmpty($response['contacts'][2]['dateAdded']);

        // Emulate an unsanitized email to ensure that doesn't cause duplicates
        $payload[0]['email'] = 'batchemail1@email.com,';

        // Set first name as null - Mautic should keep the value
        $payload[0]['firstname'] = null;

        // Remove tags from contact 1 to see if they will stick in the database
        unset($payload[0]['tags']);

        // Update others
        $payload[0]['city']             = 'Sunnyvale';
        $payload[0]['state']            = 'California';
        $payload[0]['timezone']         = 'America/Los_Angeles';
        $payload[0]['preferred_locale'] = 'en_US';
        $payload[0]['company']          = $companyB->getId();

        // Update owner
        $payload[0]['owner'] = null;
        $payload[1]['owner'] = 1;

        // Set some tags to contact 2 to see if tags update
        $payload[1]['tags'] = ['testbatch1', 'testbatch2', '-batchremovetest'];

        // Set some points to contact 2 to see if they update
        $payload[1]['points'] = 3;

        // Update the 3 contacts
        $this->client->request(Request::METHOD_POST, '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals($contactId1, $response['contacts'][0]['id']);
        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][1]);
        $this->assertEquals($contactId2, $response['contacts'][1]['id']);
        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][2]);
        $this->assertEquals($contactId3, $response['contacts'][2]['id']);

        // Assert email
        $this->assertEquals('batchemail1@email.com', $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[1]['email'], $response['contacts'][1]['fields']['all']['email']);
        $this->assertEquals($payload[2]['email'], $response['contacts'][2]['fields']['all']['email']);

        // Assert firstname
        $this->assertEquals('BatchUpdate', $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($payload[1]['firstname'], $response['contacts'][1]['fields']['all']['firstname']);
        $this->assertEquals($payload[2]['firstname'], $response['contacts'][2]['fields']['all']['firstname']);

        // Assert points
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertSame(3, $response['contacts'][1]['points']);
        $this->assertSame(3, $response['contacts'][1]['fields']['all']['points']);
        $this->assertSame(0, $response['contacts'][2]['points']);
        $this->assertSame(0, $response['contacts'][2]['fields']['all']['points']);

        // Assert tags
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals(4, count($response['contacts'][1]['tags']));
        $this->assertEquals(0, count($response['contacts'][2]['tags']));

        // Assert city
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[1]['city'], $response['contacts'][1]['fields']['all']['city']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['city']);

        // Assert state
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[1]['state'], $response['contacts'][1]['fields']['all']['state']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['state']);

        // Assert country
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[1]['country'], $response['contacts'][1]['fields']['all']['country']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['country']);

        // Assert preferred_locale
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[1]['preferred_locale'], $response['contacts'][1]['fields']['all']['preferred_locale']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['preferred_locale']);

        // Assert timezone
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[1]['timezone'], $response['contacts'][1]['fields']['all']['timezone']);
        $this->assertEquals('', $response['contacts'][2]['fields']['all']['timezone']);

        // Assert owner
        $this->assertEquals(null, $response['contacts'][0]['owner']);
        $this->assertEquals($payload[1]['owner'], $response['contacts'][1]['owner']['id']);
        $this->assertEquals(null, $response['contacts'][2]['owner']);

        // Assert company
        $this->assertEquals($companyB->getId(), (int) $response['contacts'][0]['fields']['all']['company']);
        $this->assertEquals(null, $response['contacts'][1]['fields']['all']['company']);
        $this->assertEquals(null, $response['contacts'][2]['fields']['all']['company']);
    }

    /**
     * If there are some entities to return then the response returns a hash table (JSON object),
     * So for response with no entities we must also return a JSON object because some languages
     * decode it differently then emtpty array.
     */
    public function testEmptyResponseReturnsJsonObject(): void
    {
        $this->client->request('GET', '/api/contacts?where[0][val]=unicorn&where[0][col]=email&where[0][expr]=eq');
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals('{"total":"0","contacts":{}}', $clientResponse->getContent());
    }

    public function testBatchEditEndpoint(): void
    {
        $contact = new Lead();
        $contact->setEmail('batcheditcontact1@gmail.com');

        $this->em->persist($contact);
        $this->em->flush();
        $this->em->clear();

        $payload = [
            ['email' => 'batcheditcontact1-updated@gmail.com', 'id' => $contact->getId()],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseIsSuccessful($clientResponse->getContent());

        $response= json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals($contact->getId(), $response['contacts'][0]['id']);
        $this->assertEquals('batcheditcontact1-updated@gmail.com', $response['contacts'][0]['fields']['all']['email']);
    }

    public function testBatchEditEndpointWithRubbishId(): void
    {
        $payload = [
            ['email' => 'batchemail1@email.com', 'id' => 'rubbish'],
        ];

        $this->client->request('PUT', '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseIsSuccessful($clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $this->assertGreaterThanOrEqual(1, $response['contacts'][0]['id']);
        $this->assertEquals('batchemail1@email.com', $response['contacts'][0]['fields']['all']['email']);
    }

    public function testSearchContactsWithSpecialCharacters(): void
    {
        $contact = new Lead();
        $contact->setFirstname('O\'neal');

        $this->em->persist($contact);
        $this->em->flush();

        // Test with an apostropy with URL encoding.
        $this->client->request(
            'GET',
            '/api/contacts',
            [
                'where' => [
                    [
                        'val'  => 'O\'neal',
                        'col'  => 'firstname',
                        'expr' => 'eq',
                    ],
                ],
            ]
        );
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($this->client->getResponse()->isOk(), $clientResponse->getContent());
        $payload = json_decode($clientResponse->getContent(), true);
        Assert::assertEquals(1, $payload['total']);
        $contactFromApi = $payload['contacts'][$contact->getId()];
        Assert::assertEquals($contact->getId(), $contactFromApi['id']);
        Assert::assertEquals($contact->getFirstname(), $contactFromApi['fields']['all']['firstname']);
    }

    public function testSingleNewEndpointCreateAndUpdate(): void
    {
        $payload = [
            'email'            => 'apiemail1@email.com',
            'firstname'        => 'API',
            'lastname'         => 'Update',
            'points'           => 4,
            'tags'             => ['apitest', 'testapi'],
            'city'             => 'Houston',
            'state'            => 'Texas',
            'country'          => 'United States',
            'preferred_locale' => 'es_SV',
            'timezone'         => 'America/Chicago',
            'owner'            => 1,
        ];

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, $clientResponse->getContent());

        $response  = json_decode($clientResponse->getContent(), true);
        $contactId = $response['contact']['id'];

        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEquals($payload['lastname'], $response['contact']['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($payload['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($payload['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($payload['owner'], $response['contact']['owner']['id']);

        // without overwriteWithBlank lastname is not set empty
        $payload['lastname'] = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertNotEmpty($response['contact']['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contact']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));

        // with overwriteWithBlank lastname is empty
        $payload['overwriteWithBlank'] = true;
        $payload['lastname']           = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEmpty($response['contact']['fields']['all']['lastname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($payload['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($payload['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($payload['owner'], $response['contact']['owner']['id']);

        // Lets try to create the same contact and it should merge based on unique identifier (email)
        $updatedValues = [
            'email'    => 'apiemail1@email.com',
            'lastname' => 'Update',
            'city'     => 'Boston',
            'state'    => 'Massachusetts',
            'owner'    => 2,
        ];

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($updatedValues['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEquals($updatedValues['lastname'], $response['contact']['fields']['all']['lastname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);

        // test: create the same contact, merge it based on unique identifier (email) - without loosing the owner and stage
        unset($updatedValues['owner']);

        $this->client->request('POST', '/api/contacts/new', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($updatedValues['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertEquals($updatedValues['lastname'], $response['contact']['fields']['all']['lastname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertNull($response['contact']['stage']); // stage was not set on the contact
        $this->assertSame(2, $response['contact']['owner']['id']);

        // set the owner again for the other tests to work
        $updatedValues['owner'] = 2;

        // Test getting a contact
        $this->client->request(Request::METHOD_GET, '/api/contacts/'.$contactId);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);

        // Test fetching the batch of contacts
        $this->client->request(Request::METHOD_GET, '/api/contacts');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue(isset($response['contacts'][$contactId]));
        $contact = $response['contacts'][$contactId];
        $this->assertEquals($contactId, $contact['id']);
        $this->assertEquals($payload['email'], $contact['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $contact['fields']['all']['firstname']);
        $this->assertSame(4, $contact['points']);
        $this->assertSame(4, $contact['fields']['all']['points']);
        $this->assertEquals(2, count($contact['tags']));
        $this->assertEquals($updatedValues['city'], $contact['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $contact['fields']['all']['state']);
        $this->assertEquals($payload['country'], $contact['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $contact['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $contact['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $contact['owner']['id']);

        // Test patch and values should be updated
        $updatedValues = [
            'email'  => 'apiemail1@email.com',
            'city'   => 'Boston',
            'state'  => 'Massachusetts',
            'owner'  => 2,
            'points' => 1,
        ];

        $this->client->request(
            'PATCH',
            sprintf('/api/contacts/%d/edit', $contactId),
            $updatedValues
        );
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($updatedValues['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertSame(1, $response['contact']['points']);
        $this->assertSame(1, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues['owner'], $response['contact']['owner']['id']);
    }

    /**
     * Test creating a new contact with doNotContact information.
     * The API response should include DNC information.
     */
    public function testSingleNewEndpointCreateAndDeleteWithDnc(): void
    {
        $payload = [
            'email'            => 'apidnc@email.com',
            'firstname'        => 'API',
            'lastname'         => 'DNC test',
            'points'           => 4,
            'tags'             => ['apitest', 'testapi'],
            'city'             => 'Houston',
            'state'            => 'Texas',
            'country'          => 'United States',
            'preferred_locale' => 'es_SV',
            'timezone'         => 'America/Chicago',
            'owner'            => 1,
            'doNotContact'     => [
                [
                    'channel' => 'email',
                    'reason'  => DoNotContact::BOUNCED,
                ],
            ],
        ];
        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        $this->assertEquals(1, count($response['contact']['doNotContact']));
        $this->assertEquals($payload['doNotContact'][0]['channel'], $response['contact']['doNotContact'][0]['channel']);
        $this->assertEquals($payload['doNotContact'][0]['reason'], $response['contact']['doNotContact'][0]['reason']);

        // Remove contact
        $this->client->request(Request::METHOD_DELETE, "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
    }

    public function testBatchNewEndpointCreateAndUpdate(): void
    {
        $payload = [
            [
                'email'            => 'apiemail1@email.com',
                'firstname'        => 'API',
                'lastname'         => 'Update',
                'points'           => 4,
                'tags'             => ['apitest', 'testapi'],
                'city'             => 'Houston',
                'state'            => 'Texas',
                'country'          => 'United States',
                'preferred_locale' => 'es_SV',
                'timezone'         => 'America/Chicago',
                'owner'            => 1,
            ], [
                'email'            => 'apiemail2@email.com',
                'firstname'        => 'API2',
                'lastname'         => 'Update2',
                'points'           => 3,
            ],
        ];

        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contacts'][0]['id'];

        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($payload[0]['lastname'], $response['contacts'][0]['fields']['all']['lastname']);
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[0]['owner'], $response['contacts'][0]['owner']['id']);

        // without overwriteWithBlank lastname is not set empty
        $payload[0]['lastname'] = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertNotEmpty($response['contacts'][0]['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[0]['owner'], $response['contacts'][0]['owner']['id']);

        // with overwriteWithBlank lastname is empty
        $payload[0]['overwriteWithBlank'] = true;
        $payload[0]['lastname']           = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEmpty($response['contacts'][0]['fields']['all']['lastname']);
        $this->assertEquals(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));

        // with overwriteWithBlank lastname is empty
        $payload[0]['overwriteWithBlank'] = true;
        $payload[0]['lastname']           = '';

        // Lets try to create the same contact to see that the values are not re-setted
        $this->client->request('POST', '/api/contacts/batch/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEmpty($response['contacts'][0]['fields']['all']['lastname']);
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals($payload[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($payload[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($payload[0]['owner'], $response['contacts'][0]['owner']['id']);

        // Lets try to create the same contact and it should merge based on unique identifier (email)
        $updatedValues = [
            [
                'email'    => 'apiemail1@email.com',
                'lastname' => 'Update',
                'city'     => 'Boston',
                'state'    => 'Massachusetts',
                'owner'    => 2,
            ],
        ];

        $this->client->request('POST', '/api/contacts/batch/new', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($updatedValues[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEquals($updatedValues[0]['lastname'], $response['contacts'][0]['fields']['all']['lastname']);
        $this->assertSame(4, $response['contacts'][0]['points']);
        $this->assertSame(4, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals($updatedValues[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($updatedValues[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($updatedValues[0]['owner'], $response['contacts'][0]['owner']['id']);

        // Test getting a contact
        $this->client->request('GET', '/api/contacts/'.$contactId);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contact']['id']);
        $this->assertEquals($payload[0]['email'], $response['contact']['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contact']['fields']['all']['firstname']);
        $this->assertSame(4, $response['contact']['points']);
        $this->assertSame(4, $response['contact']['fields']['all']['points']);
        $this->assertEquals(2, count($response['contact']['tags']));
        $this->assertEquals($updatedValues[0]['city'], $response['contact']['fields']['all']['city']);
        $this->assertEquals($updatedValues[0]['state'], $response['contact']['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contact']['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contact']['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contact']['fields']['all']['timezone']);
        $this->assertEquals($updatedValues[0]['owner'], $response['contact']['owner']['id']);

        // Test fetching the batch of contacts
        $this->client->request(
            'GET', '/api/contacts');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue(isset($response['contacts'][$contactId]));
        $contact = $response['contacts'][$contactId];
        $this->assertEquals($contactId, $contact['id']);
        $this->assertEquals($payload[0]['email'], $contact['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $contact['fields']['all']['firstname']);
        $this->assertSame(4, $contact['points']);
        $this->assertSame(4, $contact['fields']['all']['points']);
        $this->assertEquals(2, count($contact['tags']));
        $this->assertEquals($updatedValues[0]['city'], $contact['fields']['all']['city']);
        $this->assertEquals($updatedValues[0]['state'], $contact['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $contact['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $contact['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $contact['fields']['all']['timezone']);
        $this->assertEquals($updatedValues[0]['owner'], $contact['owner']['id']);

        // Test patch and values should be updated
        $updatedValues = [
            [
                'id'        => $contactId,
                'email'     => 'apiemail1@email.com',
                'city'      => 'Boston',
                'state'     => 'Massachusetts',
                'firstname' => '', // This will be ignored because overwriteWithBlank is false by default.
                'owner'     => 2,
                'points'    => 1,
            ],
        ];

        $this->client->request('PATCH', '/api/contacts/batch/edit', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($updatedValues[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertSame(1, $response['contacts'][0]['points']);
        $this->assertSame(1, $response['contacts'][0]['fields']['all']['points']);
        $this->assertEquals(2, count($response['contacts'][0]['tags']));
        $this->assertEquals($updatedValues[0]['city'], $response['contacts'][0]['fields']['all']['city']);
        $this->assertEquals($updatedValues[0]['state'], $response['contacts'][0]['fields']['all']['state']);
        $this->assertEquals($payload[0]['country'], $response['contacts'][0]['fields']['all']['country']);
        $this->assertEquals($payload[0]['preferred_locale'], $response['contacts'][0]['fields']['all']['preferred_locale']);
        $this->assertEquals($payload[0]['timezone'], $response['contacts'][0]['fields']['all']['timezone']);
        $this->assertEquals($updatedValues[0]['owner'], $response['contacts'][0]['owner']['id']);

        // with overwriteWithBlank lastname is empty
        $updatedValues = [
            [
                'id'                 => $contactId,
                'lastname'           => '',
                'overwriteWithBlank' => true,
            ],
        ];

        $this->client->request('PATCH', '/api/contacts/batch/edit', $updatedValues);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($contactId, $response['contacts'][0]['id']);
        $this->assertEquals($payload[0]['email'], $response['contacts'][0]['fields']['all']['email']);
        $this->assertEquals($payload[0]['firstname'], $response['contacts'][0]['fields']['all']['firstname']);
        $this->assertEmpty($response['contacts'][0]['fields']['all']['lastname']);
    }

    public function testBatchDncAddAndRemove(): void
    {
        // Create contact
        $emailAddress = uniqid('', false).'@mautic.com';

        $payload = [
            'id'    => 80,
            'email' => $emailAddress,
        ];

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        // Batch update contact with new DNC record
        $payload = [
            [
                'id'           => $contactId,
                'email'        => $emailAddress,
                'doNotContact' => [
                    [
                        'reason'    => DoNotContact::MANUAL,
                        'comments'  => 'manually',
                        'channel'   => 'email',
                        'channelId' => null,
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(3, $response['contacts'][0]['doNotContact'][0]['reason']);

        // Batch update contact and remove DNC record
        $payload = [
            [
                'id'           => $contactId,
                'email'        => $emailAddress,
                'doNotContact' => [
                    [
                        'reason'    => DoNotContact::IS_CONTACTABLE,
                        'comments'  => 'manually',
                        'channel'   => 'email',
                        'channelId' => null,
                    ],
                ],
            ],
        ];

        $this->client->request(Request::METHOD_PUT, '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEmpty($response['contacts'][0]['doNotContact']);

        // Remove contact
        $this->client->request(Request::METHOD_DELETE, "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
    }

    public function testBatchEditEndpointSameContact(): void
    {
        $contact1 = new Lead();
        $contact1->setEmail('batcheditcontact1@gmail.com');
        $this->em->persist($contact1);

        $contact2 = new Lead();
        $contact2->setEmail('batcheditcontact2@gmail.com');
        $this->em->persist($contact2);

        $this->em->flush();
        $this->em->clear();

        $payload = [
            ['points' => 1, 'id' => $contact1->getId()],
            ['points' => 1, 'id' => $contact2->getId()],
            ['points' => 2, 'id' => $contact1->getId()],
        ];

        $this->client->request(Request::METHOD_PATCH, '/api/contacts/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();

        self::assertResponseIsSuccessful($clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        self::assertCount(3, $response['contacts']);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertSame($contact1->getId(), $response['contacts'][0]['id']);
        $this->assertSame(2, $response['contacts'][0]['points']);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][1]);
        $this->assertSame($contact2->getId(), $response['contacts'][1]['id']);
        $this->assertSame(1, $response['contacts'][1]['points']);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][2]);
        $this->assertSame($contact1->getId(), $response['contacts'][2]['id']);
        $this->assertSame(2, $response['contacts'][2]['points']);
    }

    public function testAddAndRemoveDncToExistingContact(): void
    {
        // Create contact
        $payload = ['email' => 'addDncDemo@mautic.org'];

        $this->client->request(Request::METHOD_POST, '/api/contacts/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $contactId      = $response['contact']['id'];

        // Ensure that doNotContact is empty after creating the contact
        $this->assertSame([], $response['contact']['doNotContact']);

        // Check with a DNC payload that has an empty reasoncode in it, this should throw a 400 Bad Request error
        $dncPayload = ['reason' => 0];
        $dncChannel = 'email';

        $this->client->request(Request::METHOD_POST, "/api/contacts/$contactId/dnc/$dncChannel/add", $dncPayload);
        $clientResponse = $this->client->getResponse();
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Leave the DNC payload empty to ensure it takes default values for channel and reason.
        $dncPayload = [];

        // Add DNC to the contact.
        $this->client->request(Request::METHOD_POST, "/api/contacts/$contactId/dnc/$dncChannel/add", $dncPayload);
        $clientResponse = $this->client->getResponse();
        $dncResponse    = json_decode($clientResponse->getContent(), true);

        // MANUAL (3) is the default value according to the dev docs: https://developer.mautic.org/#add-do-not-contact
        $this->assertSame(DoNotContact::MANUAL, $dncResponse['contact']['doNotContact'][0]['reason']);
        $this->assertSame($dncChannel, $dncResponse['contact']['doNotContact'][0]['channel']);

        // Check DNC is recorded in the contact activity.
        $this->client->request('GET', "/api/contacts/{$contactId}/activity");
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful($clientResponse->getContent());
        $activityResponse = json_decode($clientResponse->getContent(), true);
        Assert::assertCount(2, $activityResponse['events']); // identified and dnc added events
        $dncEvents = array_values(array_filter($activityResponse['events'], fn ($event) => 'lead.donotcontact' === $event['event']));
        Assert::assertCount(1, $dncEvents);
        Assert::assertSame('Email', $dncEvents[0]['eventLabel']);
        Assert::assertSame('Contact was manually set as do not contact for this channel.', $dncEvents[0]['details']['dnc']['reason']);

        // Remove DNC from the contact.
        $this->client->request(Request::METHOD_POST, "/api/contacts/$contactId/dnc/$dncChannel/remove");
        $clientResponse    = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $dncRemoveResponse = json_decode($clientResponse->getContent(), true);

        $this->assertArrayHasKey('contact', $dncRemoveResponse, $clientResponse->getContent());
        $this->assertSame([], $dncRemoveResponse['contact']['doNotContact']);

        // Remove the contact.
        $this->client->request(Request::METHOD_DELETE, "/api/contacts/$contactId/delete");
        $clientResponse = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
    }

    public function testGetAllActivityDownload(): void
    {
        $expectedActivites = 0;
        for ($i = 0; $i < 10; ++$i) {
            $contact = new Lead();
            $contact->setEmail('email'.(string) $i.'@acquia.cz');
            $this->em->persist($contact);
            // +30 assets downloads
            $expectedActivites += 3;
            for ($iAsset = 0; $iAsset < 3; ++$iAsset) {
                $ipAddress = new IpAddress();
                $ipAddress->setIpAddress('13.13.13.13');
                $this->em->persist($ipAddress);
                $assetDownload = new Download();
                $assetDownload->setLead($contact);
                $assetDownload->setDateDownload(date_create('2013-03-15'));
                $assetDownload->setCode(13);
                $assetDownload->setTrackingId(13);
                $assetDownload->setIpAddress($ipAddress);
                $this->em->persist($assetDownload);
            }
            // +30 lead event logs + 30 events
            $expectedActivites += 6;
            for ($iEvent = 0; $iEvent < 3; ++$iEvent) {
                $campaign = new Campaign();
                $campaign->setName('Test Campaign');
                $this->em->persist($campaign);
                $event = new Event();
                $event->setName('Test Event');
                $event->setType('typeTest');
                $event->setEventType('eventTypeTest');
                $event->setCampaign($campaign);
                $this->em->persist($event);
                $leadEventLog = new LeadEventLog();
                $leadEventLog->setEvent($event);
                $leadEventLog->setLead($contact);
                $this->em->persist($leadEventLog);
            }
            // +30 do not contact
            $expectedActivites += 3;
            for ($iDoNotContact = 0; $iDoNotContact < 3; ++$iDoNotContact) {
                $doNotContact = new DoNotContact();
                $doNotContact->setLead($contact);
                $doNotContact->setDateAdded(date_create('2013-03-15'));
                $doNotContact->setChannel('email');
                $this->em->persist($doNotContact);
            }
            // +30 dynamic content stats
            $expectedActivites += 3;
            for ($iStat = 0; $iStat < 3; ++$iStat) {
                $stat = new StatDC();
                $stat->setLead($contact);
                $stat->setDateSent(date_create('2013-03-15'));
                $this->em->persist($stat);
            }
            // +30 email stats
            $expectedActivites += 3;
            for ($iEmailStat = 0; $iEmailStat < 3; ++$iEmailStat) {
                $stat = new StatEmail();
                $stat->setLead($contact);
                $stat->setEmailAddress('email'.(string) $i.'@acquia.cz');
                $stat->setDateSent(date_create('2013-03-15'));
                $this->em->persist($stat);
            }
        }

        // Save to DB
        $this->em->flush();

        // Call endpoint
        $this->client->request('GET', '/api/contacts/activity');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $responseJson = json_decode($clientResponse->getContent());
        $this->assertSame($expectedActivites, $responseJson->total);
    }

    public function testGetActivityInRightOrder(): void
    {
        $contact = new Lead();
        $contact->setEmail('email@acquia.cz');
        $this->em->persist($contact);

        $dates = ['2013-03-15', '2013-03-10', '2013-03-05', '2013-03-20', '2013-03-25'];
        foreach ($dates as $date) {
            $stat = new StatDC();
            $stat->setLead($contact);
            $stat->setDateSent(date_create($date));
            $this->em->persist($stat);
        }

        // Save to DB
        $this->em->flush();

        // Expected stat order
        $expectedDatesOrder = ['2013-03-25', '2013-03-20', '2013-03-15', '2013-03-10', '2013-03-05'];

        // Call endpoint
        $this->client->request('GET', '/api/contacts/'.(string) $contact->getId().'/activity');
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        $responseJson = json_decode($clientResponse->getContent());
        $resultOrder  = [];
        foreach ($responseJson->events as $event) {
            $resultOrder[] = substr($event->timestamp, 0, 10);
        }
        $this->assertSame($expectedDatesOrder, $resultOrder);
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);

        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }
}
