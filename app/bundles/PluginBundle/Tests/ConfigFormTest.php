<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Test;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Entity\IntegrationRepository;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Entity\PluginRepository;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\PluginModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfigFormTest extends KernelTestCase
{
    protected $container;

    protected function setUp()
    {
        self::bootKernel();
        $this->container = self::$kernel->getContainer();
    }

    public function testConfigForm()
    {
        $plugins        = $this->getIntegrationObject()->getIntegrationObjects();
        $mockTranslator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        foreach ($plugins as $name => $s) {
            $s->setTranslator($mockTranslator);

            $featureSettings = $s->getFormSettings();

            $this->assertArrayHasKey('requires_callback', $featureSettings);
            $this->assertArrayHasKey('requires_authorization', $featureSettings);
            if ($featureSettings['requires_callback']) {
                $this->assertNotEmpty($s->getAuthCallbackUrl());
            }
        }
    }

    public function testOauth()
    {
        $plugins = $this->getIntegrationObject()->getIntegrationObjects();

        $url        = 'https://test.com';
        $parameters = ['a' => 'testa', 'b' => 'testb'];
        $method     = 'GET';
        $authType   = 'oauth2';
        foreach ($plugins as $s) {
            $s->prepareRequest($url, $parameters, $method, [], $authType);
        }
    }

    public function testAmendLeadDataBeforeMauticPopulate()
    {
        $plugins = $this->getIntegrationObject()->getIntegrationObjects();

        $object = 'company';
        $data   = ['company_name' => 'company_name', 'email' => 'company_email'];
        foreach ($plugins as $name => $s) {
            if (method_exists($s, 'amendLeadDataBeforeMauticPopulate')) {
                $count = $s->amendLeadDataBeforeMauticPopulate($data, $object);
                $this->assertGreaterThanOrEqual(0, $count);
            }
        }
    }

    public function getIntegrationObject()
    {
        //create an integration object
        $pathsHelper          = $this->getMockBuilder(PathsHelper::class)->disableOriginalConstructor()->getMock();
        $bundleHelper         = $this->getMockBuilder(BundleHelper::class)->disableOriginalConstructor()->getMock();
        $pluginModel          = $this->getMockBuilder(PluginModel::class)->disableOriginalConstructor()->getMock();
        $coreParametersHelper = new CoreParametersHelper(self::$kernel);
        $templatingHelper     = $this->getMockBuilder(TemplatingHelper::class)->disableOriginalConstructor()->getMock();
        $entityManager        = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pluginRepository = $this
            ->getMockBuilder(PluginRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $registeredPluginBundles = $this->container->getParameter('mautic.plugin.bundles');
        $mauticPlugins           = $this->container->getParameter('mautic.bundles');
        $bundleHelper->expects($this->any())->method('getPluginBundles')->willReturn([$registeredPluginBundles]);

        $bundleHelper->expects($this->any())->method('getMauticBundles')->willReturn(array_merge($mauticPlugins, $registeredPluginBundles));
        $integrationEntityRepository = $this
            ->getMockBuilder(IntegrationEntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $integrationRepository = $this
            ->getMockBuilder(IntegrationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this
            ->any())
                ->method('getRepository')
                ->will(
                    $this->returnValueMap(
                            [
                                ['MauticPluginBundle:Plugin', $pluginRepository],
                                ['MauticPluginBundle:Integration', $integrationRepository],
                                ['MauticPluginBundle:IntegrationEntity', $integrationEntityRepository],
                            ]
                    )
                );

        $integrationHelper = new IntegrationHelper(
            self::$kernel,
            $entityManager,
            $pathsHelper,
            $bundleHelper,
            $coreParametersHelper,
            $templatingHelper,
            $pluginModel
            );

        return $integrationHelper;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
    }
}
