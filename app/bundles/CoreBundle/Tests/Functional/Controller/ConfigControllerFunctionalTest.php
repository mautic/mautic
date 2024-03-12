<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ConfigControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testPluginConfigurationFormHasTheCustomFields(): void
    {
        $name   = 'Clearbit';
        $apiKey = '$om3R@nd0m#';
        $this->createIntegration($name);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/plugins/config/'.$name);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());

        $buttonCrawler = $crawler->selectButton('integration_details[buttons][save]');

        $payload = [
            'integration_details' => [
                'isPublished' => 1,
                'apiKeys'     => [
                    'apikey'      => $apiKey,
                ],
            ],
        ];
        $this->client->submit($buttonCrawler->form(), $payload);

        // Find the integration
        /* @var IntegrationHelper $integrationsHelper */
        $integrationsHelper = $this->getContainer()->get('mautic.helper.integration');

        /** @var ClearbitIntegration $clearBitIntegration */
        $clearBitIntegration = $integrationsHelper->getIntegrationObject('Clearbit');
        $keys                = $clearBitIntegration->getDecryptedApiKeys();

        $this->assertNotEmpty($keys);
        $this->assertSame($keys['apikey'], $apiKey);
    }

    private function createIntegration(string $name): void
    {
        $plugin = new Plugin();
        $plugin->setName($name);
        $plugin->setBundle('MauticClearbitBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setIsPublished(true);
        $integration->setName($plugin->getName());
        $this->em->persist($integration);

        $this->em->flush();
    }
}
