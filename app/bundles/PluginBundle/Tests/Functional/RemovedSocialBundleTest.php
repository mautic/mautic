<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\Request;

final class RemovedSocialBundleTest extends MauticMysqlTestCase
{
    public function testReloadDisablesRemovedPluginAndPreservesItsSettings(): void
    {
        $plugin = new Plugin();
        $plugin->setName('Social Media');
        $plugin->setBundle('MauticSocialBundle');
        $this->em->persist($plugin);

        $integration = new Integration();
        $integration->setPlugin($plugin);
        $integration->setName('Twitter');
        $integration->setIsPublished(true);
        $integration->setFeatureSettings(['existing_setting' => 'preserved']);
        $this->em->persist($integration);
        $this->em->flush();

        // Repeated reloads must preserve settings while keeping the removed plugin disabled.
        for ($reload = 0; $reload < 2; ++$reload) {
            $this->testSymfonyCommand('mautic:plugins:reload')->assertCommandIsSuccessful();
            $this->em->refresh($plugin);
            $this->em->refresh($integration);

            self::assertTrue($plugin->getIsMissing());
            self::assertTrue($integration->getIsPublished());
            self::assertSame(['existing_setting' => 'preserved'], $integration->getFeatureSettings());
        }

        $integrations = self::getContainer()->get(IntegrationHelper::class)->getIntegrationObjects();
        self::assertArrayNotHasKey('Twitter', $integrations);
        self::assertArrayHasKey('Salesforce', $integrations);

        $crawler = $this->client->request(Request::METHOD_GET, '/s/plugins');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Social Media', $crawler->filter('#app-content')->text());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/config/edit');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Social Settings', $crawler->filter('.list-group-tabs')->text());
        self::assertStringNotContainsString('Social Monitoring', $crawler->filter('.sidebar-left .sidebar-content')->text());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/forms/new');
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Social Login', $crawler->filter('#fields-container select.form-builder-new-component')->text());
    }
}
