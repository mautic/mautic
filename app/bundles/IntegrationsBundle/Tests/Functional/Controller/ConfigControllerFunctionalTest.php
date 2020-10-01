<?php

namespace Mautic\IntegrationsBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpKernel\KernelInterface;

class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    private $payload;

    /**
     * Install plugins.
     *
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        /** @var KernelInterface $kernel */
        $kernel      = $this->client->getContainer()->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'mautic:plugins:install',
            '--quiet' => true,
        ]);
        $application->run($input);

        $this->payload = [
            'integration_config' => [
                    'isPublished' => 1,
                    'apiKeys'     => [
                            'consumer_id'     => 'secretId',
                            'consumer_secret' => 'secretSecret',
                        ],
                ],
            'callback_url'      => '/integration/Salesforce2/callback',
            'supportedFeatures' => [
                    'sync',
                ],
            'featureSetting' => [
                    'sync' => [
                            'objects' => [
                                    'Lead',
                                    'Contact',
                                    'Account',
                                ],
                        ],
                    'integration' => [
                            'syncDateForm' => '1950-02-13',
                        ],
                ],
        ];
    }

    /**
     * For non authorized user the validation should not break on form validation.
     */
    public function testSubmitFormWhenNotAuthorized(): void
    {
        $payload                                   = $this->payload;
        $payload['integration_details']['in_auth'] = 1;

        $this->client->request('POST', '/s/integration/Salesforce2/config', $payload);
        $clientResponse  = $this->client->getResponse();
        $valuesResponse  = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertArrayHasKey('closeModal', $valuesResponse, 'The return must contain key closeModal');
    }

    /**
     * For authorized user the validation should break on form validation if the form is not valid.
     * ConfigFormAuthorizeButtonInterface is used interface in ConfigSupport class.
     */
    public function testSubmitFormWhenAuthorizedConfigFormAuthorizeButtonInterfaceUsed(): void
    {
        // Payload
        $payload                                   = $this->payload;
        $payload['integration_details']['in_auth'] = 0;
        $apiKeys                                   = [
            'consumer_id'     => 'secretId',
            'consumer_secret' => 'secretSecret',
            'access_token'    => '123',
            'refresh_token'   => '456',
            'expires_at'      => 10,
            'instance_url'    => 'url',
        ];
        // Salesforce setting
        /** @var IntegrationEntityModel $model */
        $model      = $this->container->get('mautic.plugin.model.integration_entity');
        $salesforce = new Integration();
        $salesforce->setIsPublished(true)
            ->setApiKeys($apiKeys)
            ->setName('Salesforce2');
        $model->saveEntity($salesforce);

        // Request
        $this->client->request('POST', '/s/integration/Salesforce2/config', $payload);
        $clientResponse  = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringNotContainsString('closeModal', $clientResponse, 'The return must not contain key closeModal');
    }

    /**
     * For authorized user the validation should break on form validation if the form is not valid.
     * ConfigFormAuthorizeButtonInterface is not used interface in ConfigSupport class.
     */
    public function testSubmitFormWhenAuthorizedConfigFormAuthorizeButtonInterfaceNotUsed(): void
    {
        $this->markTestSkipped();

        // When running complete test I'm getting
        // 2020-09-30 14:12:37] mautic.ERROR: SCHEMA ERROR: An exception occurred while executing 'SELECT f.alias, f.is_unique_identifer as is_unique, f.is_index, f.type, f.object FROM lead_fields f WHERE f.object = 'lead' ORDER BY f.field_order ASC':
        // SQLSTATE[42S02]: Base table or view not found: 1146 Table 'mautictest.lead_fields' doesn't exist

        // Payload
        $payload                                   = $this->payload;
        $payload['integration_details']['in_auth'] = 0;
        $apiKeys                                   = [
            'key'     => 'secretId',
            'secret'  => 'secretSecret',
            'host'    => 'https://api.stage.brighttalk.com',
        ];
        // Salesforce setting
        /** @var IntegrationEntityModel $model */
        $model      = $this->container->get('mautic.plugin.model.integration_entity');
        $salesforce = new Integration();
        $salesforce->setIsPublished(true)
            ->setApiKeys($apiKeys)
            ->setName('BrightTalk');
        $model->saveEntity($salesforce);

        // Request
        $this->client->request('POST', '/s/integration/BrightTalk/config', $payload);
        $clientResponse  = $this->client->getResponse();
        $this->assertSame(200, $clientResponse->getStatusCode(), 'Return code must be 200.');
        $this->assertStringNotContainsString('closeModal', $clientResponse, 'The return must not contain key closeModal');
    }
}
