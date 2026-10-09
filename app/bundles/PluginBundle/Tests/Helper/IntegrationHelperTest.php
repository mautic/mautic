<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Helper;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\BundleHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Model\PluginModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

final class IntegrationHelperTest extends TestCase
{
    private IntegrationHelper $integrationHelper;

    protected function setUp(): void
    {
        $this->integrationHelper = new IntegrationHelper(
            $this->createStub(ContainerInterface::class),
            $this->createStub(EntityManager::class),
            $this->createStub(PathsHelper::class),
            $this->createStub(BundleHelper::class),
            $this->createStub(CoreParametersHelper::class),
            $this->createStub(Environment::class),
            $this->createStub(PluginModel::class),
        );
    }

    public function testCurrentSocialProfileUrlsAreAvailable(): void
    {
        $urls = $this->integrationHelper->getSocialProfileUrlRegex(false);

        $this->assertSame('https://x.com/%handle%', $urls['twitter']);
        $this->assertSame('https://tiktok.com/@%handle%', $urls['tiktok']);
        $this->assertSame('https://youtube.com/@%handle%', $urls['youtube']);
    }

    public function testCurrentAndLegacyXDomainsCanBeParsed(): void
    {
        $patterns = $this->integrationHelper->getSocialProfileUrlRegex();

        $this->assertIsArray($patterns['twitter']);
        $this->assertCount(2, $patterns['twitter']);
        $this->assertMatchesRegularExpression($patterns['twitter'][0], 'https://x.com/mautic');
        $this->assertMatchesRegularExpression($patterns['twitter'][1], 'https://twitter.com/mautic');
        $this->assertMatchesRegularExpression($patterns['tiktok'], 'https://tiktok.com/@mautic');
        $this->assertMatchesRegularExpression($patterns['youtube'][0], 'https://youtube.com/@mautic');
    }
}
