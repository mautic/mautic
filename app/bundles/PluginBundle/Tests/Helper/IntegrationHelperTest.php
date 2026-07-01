<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use PHPUnit\Framework\TestCase;

final class IntegrationHelperTest extends TestCase
{
    private IntegrationHelper $integrationHelper;

    protected function setUp(): void
    {
        $this->integrationHelper = (new \ReflectionClass(IntegrationHelper::class))->newInstanceWithoutConstructor();
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
