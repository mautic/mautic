<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Facade\EncryptionService;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\EventHelper;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class PushContactTest extends MauticMysqlTestCase
{
    private const string API_KEY = 'abc123-us7';

    /**
     * @var list<array{method: string, url: string, options: array<string, mixed>}>
     */
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $plugin = new Plugin();
        $plugin->setName('Email Marketing');
        $plugin->setBundle('MauticEmailMarketingBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setName('Mailchimp');
        $integration->setPlugin($plugin);
        $integration->setIsPublished(true);
        $integration->setSupportedFeatures(['push_lead']);
        // Encrypted key by key, as the former integration saved them
        $integration->setApiKeys(self::getContainer()->get(EncryptionService::class)->encrypt([
            'username' => 'mautic',
            'password' => self::API_KEY,
        ]));
        $this->em->persist($integration);

        $this->em->flush();
    }

    public function testPushActionSubscribesTheContactWithAMappingSavedByTheFormerForm(): void
    {
        $this->mockResponses(new MockResponse('{"id":"member"}'));

        $contact = new Lead();
        $contact->setEmail('john@example.com');
        $contact->setFirstname('John');
        $this->em->persist($contact);
        $this->em->flush();
        $contactId = $contact->getId();
        $this->em->clear();

        $pushed = self::getContainer()->get(EventHelper::class)->pushLead([
            'integration' => 'Mailchimp',
            'config'      => [
                'list_settings' => [
                    'list'        => 'list-1',
                    'doubleOptin' => '1',
                    'sendWelcome' => '0',
                    // Stored by the former field matching form, which also kept the list settings in its data
                    'leadFields'  => [
                        'list'        => 'list-1',
                        'doubleOptin' => '1',
                        'sendWelcome' => '0',
                        'i_1'         => 'EMAIL',
                        'm_1'         => 'email',
                        'EMAIL'       => '1',
                        'i_2'         => 'FNAME',
                        'm_2'         => 'firstname',
                        'FNAME'       => '2',
                    ],
                ],
            ],
        ], $contactId);

        $this->assertTrue($pushed);
        $this->assertCount(1, $this->requests);
        $this->assertSame('POST', $this->requests[0]['method']);
        $this->assertSame('https://us7.api.mailchimp.com/3.0/lists/list-1/members', $this->requests[0]['url']);
        $this->assertContains('Authorization: Basic '.base64_encode('mautic:'.self::API_KEY), $this->requests[0]['options']['normalized_headers']['authorization']);

        $body = json_decode((string) $this->requests[0]['options']['body'], true);
        $this->assertSame('john@example.com', $body['email_address']);
        $this->assertSame('pending', $body['status']);
        $this->assertSame(['FNAME' => 'John'], $body['merge_fields']);
    }

    public function testPushActionFailsWhenMailchimpRejectsTheContact(): void
    {
        $this->mockResponses(new MockResponse('{"title":"Invalid Resource","status":400,"detail":"john@example.com looks fake or invalid."}', ['http_code' => 400]));

        $contact = new Lead();
        $contact->setEmail('john@example.com');
        $this->em->persist($contact);
        $this->em->flush();
        $contactId = $contact->getId();
        $this->em->clear();

        $pushed = self::getContainer()->get(EventHelper::class)->pushLead([
            'integration' => 'Mailchimp',
            'config'      => [
                'list_settings' => [
                    'list'        => 'list-1',
                    'doubleOptin' => '0',
                    'sendWelcome' => '0',
                    'leadFields'  => ['EMAIL' => 'email'],
                ],
            ],
        ], $contactId);

        $this->assertFalse($pushed);
        $this->assertCount(1, $this->requests);
    }

    public function testIntegrationConfigReloadShowsTheMergeFieldsOfTheSelectedList(): void
    {
        $this->mockResponses(
            new MockResponse('{"lists":[{"id":"list-1","name":"Newsletter"}]}'),
            new MockResponse('{"merge_fields":[{"tag":"FNAME","name":"First Name","required":false}]}'),
        );

        $this->client->request('GET', '/s/ajax', [
            'action'      => 'plugin:getIntegrationConfig',
            'integration' => 'Mailchimp',
            'settings'    => [
                'name'   => 'campaignevent[properties][integration]',
                'config' => ['list_settings' => ['list' => 'list-1']],
            ],
        ]);
        $this->assertResponseIsSuccessful();

        $response = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertSame(1, $response['success']);
        $this->assertStringContainsString('name="campaignevent[properties][config][list_settings][list]"', (string) $response['html']);
        $this->assertStringContainsString('<option value="list-1" selected="selected">Newsletter</option>', (string) $response['html']);
        $this->assertStringContainsString('name="campaignevent[properties][config][list_settings][leadFields][EMAIL]"', (string) $response['html']);
        $this->assertStringContainsString('name="campaignevent[properties][config][list_settings][leadFields][FNAME]"', (string) $response['html']);
        $this->assertSame('https://us7.api.mailchimp.com/3.0/lists/list-1/merge-fields', $this->requests[1]['url']);
    }

    private function mockResponses(MockResponse ...$responses): void
    {
        $httpClient = self::getContainer()->get(MockHttpClient::class);
        $this->assertInstanceOf(MockHttpClient::class, $httpClient);
        $httpClient->setResponseFactory(function (string $method, string $url, array $options) use (&$responses): MockResponse {
            $this->requests[] = ['method' => $method, 'url' => strtok($url, '?'), 'options' => $options];

            return array_shift($responses) ?? new MockResponse('{}');
        });
    }
}
