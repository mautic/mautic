<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RemovedCrmBundleTest extends MauticMysqlTestCase
{
    public function testReloadDisablesRemovedPluginAndPreservesItsSettings(): void
    {
        $plugin = new Plugin();
        $plugin->setName('CRM');
        $plugin->setBundle('MauticCrmBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setName('Salesforce');
        $integration->setIsPublished(true);
        $integration->setFeatureSettings(['existing_setting' => 'preserved']);
        $this->em->persist($integration);
        $this->em->flush();

        // Repeated reloads must preserve settings while keeping the removed plugin disabled.
        for ($reload = 0; $reload < 2; ++$reload) {
            $this->testSymfonyCommand('mautic:plugins:reload')->assertCommandIsSuccessful();
            $this->em->refresh($plugin);
            $this->em->refresh($integration);

            $this->assertTrue($plugin->getIsMissing());
            $this->assertTrue($integration->getIsPublished());
            $this->assertSame(['existing_setting' => 'preserved'], $integration->getFeatureSettings());
        }

        $integrations = $this->getService(IntegrationHelper::class)->getIntegrationObjects();
        foreach (['Salesforce', 'Hubspot', 'Zoho', 'Dynamics', 'Sugarcrm', 'Connectwise', 'Vtiger'] as $removedIntegration) {
            $this->assertArrayNotHasKey($removedIntegration, $integrations);
        }
        $this->assertArrayHasKey('Twilio', $integrations);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/plugins');
        self::assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Salesforce', $crawler->filter('#app-content')->text());

        $this->client->request(Request::METHOD_POST, '/plugin/Salesforce/contact_data');
        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
