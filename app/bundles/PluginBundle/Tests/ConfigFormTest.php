<?php

namespace Mautic\PluginBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Entity\PluginRepository;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class ConfigFormTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testConfigForm(): void
    {
        $plugins = $this->getIntegrationObject()->getIntegrationObjects();

        foreach ($plugins as $name => $s) {
            $featureSettings = $s->getFormSettings();

            $this->assertArrayHasKey('requires_callback', $featureSettings);
            $this->assertArrayHasKey('requires_authorization', $featureSettings);
            if ($featureSettings['requires_callback']) {
                $this->assertNotEmpty($s->getAuthCallbackUrl());
            }
        }
    }

    public function testOauth(): void
    {
        $plugins    = $this->getIntegrationObject()->getIntegrationObjects();
        $url        = 'https://test.com';
        $parameters = ['a' => 'testa', 'b' => 'testb'];
        $method     = 'GET';
        $authType   = 'oauth2';

        $expected                = [];
        $expected['Connectwise'] = $this->getOauthData('');
        $expected['OneSignal']   = $this->getOauthData('');
        $expected['Twilio']      = $this->getOauthData('');
        $expected['Vtiger']      = $this->getOauthData('sessionName');
        $expected['Dynamics']    = $this->getOauthData('access_token');
        $expected['Salesforce']  = $this->getOauthData('access_token');
        $expected['Sugarcrm']    = $this->getOauthData('access_token');
        $expected['Zoho']        = $this->getOauthData('access_token');
        $expected['Hubspot']     = $this->getOauthData('hapikey');

        foreach ($plugins as $index => $integration) {
            $this->assertSame($expected[$index], $integration->prepareRequest($url, $parameters, $method, [], $authType));
        }
    }

    /**
     * @return array<mixed>
     */
    private function getOauthData(string $key): array
    {
        return [
            [
                'a'   => 'testa',
                'b'   => 'testb',
                $key  => '',
            ], [
                'oauth-token: '.$key,
                'Authorization: OAuth ',
            ],
        ];
    }

    public function testAmendLeadDataBeforeMauticPopulate(): void
    {
        $plugins = $this->getIntegrationObject()->getIntegrationObjects();
        $object  = 'company';
        $data    = ['company_name' => 'company_name', 'email' => 'company_email'];

        foreach ($plugins as $integration) {
            $methodExists = method_exists($integration, 'amendLeadDataBeforeMauticPopulate');
            if ($methodExists) {
                $count = $integration->amendLeadDataBeforeMauticPopulate($data, $object);
                $this->assertGreaterThanOrEqual(0, $count);
            }
        }
    }

    public function getIntegrationObject()
    {
        // create an integration object
        $pathsHelper          = $this->getMockBuilder(PathsHelper::class)->disableOriginalConstructor()->getMock();
        $bundleHelper         = $this->getMockBuilder(BundleHelper::class)->disableOriginalConstructor()->getMock();
        $pluginModel          = $this->getMockBuilder(PluginModel::class)->disableOriginalConstructor()->getMock();
        $coreParametersHelper = new CoreParametersHelper(self::$kernel->getContainer());
        $twig                 = $this->getMockBuilder(Environment::class)->disableOriginalConstructor()->getMock();
        $entityManager        = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pluginRepository = $this
            ->getMockBuilder(PluginRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $registeredPluginBundles = static::getContainer()->getParameter('mautic.plugin.bundles');
        $mauticPlugins           = static::getContainer()->getParameter('mautic.bundles');
        $bundleHelper->method('getPluginBundles')->willReturn($registeredPluginBundles);

        $bundleHelper->method('getMauticBundles')->willReturn(array_merge($mauticPlugins, $registeredPluginBundles));
        $integrationEntityRepository = $this
            ->getMockBuilder(IntegrationEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integrationRepository = $this
            ->getMockBuilder(IntegrationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager
                ->method('getRepository')
                ->willReturnMap(
                    [
                        [\Mautic\PluginBundle\Entity\Plugin::class, $pluginRepository],
                        [\Mautic\PluginBundle\Entity\Integration::class, $integrationRepository],
                        [\Mautic\PluginBundle\Entity\IntegrationEntity::class, $integrationEntityRepository],
                    ]
                );

        $pluginModel->method('getEntities')
            ->with(
                [
                    'hydration_mode' => 'hydrate_array',
                    'index'          => 'bundle',
                    'result_cache'   => new ResultCacheOptions(Plugin::CACHE_NAMESPACE),
                ]
            )->willReturn([
                'MauticCrmBundle' => ['id' => 1],
            ]);

        $integrationHelper = new IntegrationHelper(
            self::getContainer(),
            $entityManager,
            $pathsHelper,
            $bundleHelper,
            $coreParametersHelper,
            $twig,
            $pluginModel
        );

        return $integrationHelper;
    }
}
