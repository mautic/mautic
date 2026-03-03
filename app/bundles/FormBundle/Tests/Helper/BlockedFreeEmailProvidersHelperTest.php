<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Helper;

use Mautic\FormBundle\Helper\BlockedFreeEmailProvidersHelper;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(BlockedFreeEmailProvidersHelper::class)]
final class BlockedFreeEmailProvidersHelperTest extends TestCase
{
    public function testLoadReturnsArrayFromValidJsonFile(): void
    {
        $providers = BlockedFreeEmailProvidersHelper::load();

        self::assertIsArray($providers);
        self::assertNotEmpty($providers);
        self::assertContainsOnly('string', $providers);
    }

    public function testLoadReturnsArrayOfStrings(): void
    {
        $providers = BlockedFreeEmailProvidersHelper::load();

        if (!empty($providers)) {
            foreach ($providers as $provider) {
                self::assertIsString($provider);
                self::assertNotEmpty($provider);
            }
        }
    }

    public function testLoadReturnsNonEmptyArray(): void
    {
        $providers = BlockedFreeEmailProvidersHelper::load();

        // The JSON file should contain providers
        self::assertGreaterThan(0, count($providers));
    }
}
