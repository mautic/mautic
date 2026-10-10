<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Twig;

use MauticPlugin\MauticFocusBundle\Twig\Extension\FocusBundleExtension;
use PHPUnit\Framework\Attributes\DataProvider;
use Twig\Extension\ExtensionInterface;

/**
 * @see https://twig.symfony.com/doc/3.x/advanced.html#functional-tests
 */
final class TwigIntegrationTest extends \Twig\Test\IntegrationTestCase
{
    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new FocusBundleExtension(),
        ];
    }

    protected static function getFixturesDirectory(): string
    {
        $reflection = new \ReflectionClass(self::class);

        return dirname($reflection->getFileName()).'/Fixtures/';
    }

    /**
     * Static data provider to satisfy PHPUnit 10's static requirement.
     *
     * @return iterable<array{string, string, string, array<string, string>, string|false, array<array{string|null, string, string|null, string}>, string}>
     */
    public static function integrationTestDataProvider(): iterable
    {
        $reflection = new \ReflectionClass(self::class);
        $instance   = $reflection->newInstanceWithoutConstructor();

        return $instance->getTests('testIntegration', false);
    }

    /**
     * @param string                $file
     * @param string                $message
     * @param string                $condition
     * @param array<string, string> $templates
     * @param string|bool           $exception
     * @param array<mixed>          $outputs
     * @param string                $deprecation
     */
    #[DataProvider('integrationTestDataProvider')]
    public function testIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = ''): void
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    /**
     * Legacy Twig features are not used, so this test is skipped.
     *
     * @param mixed $file
     * @param mixed $message
     * @param mixed $condition
     * @param mixed $templates
     * @param mixed $exception
     * @param mixed $outputs
     * @param mixed $deprecation
     */
    public function testLegacyIntegration($file = null, $message = null, $condition = null, $templates = null, $exception = null, $outputs = null, $deprecation = ''): void
    {
        $this->markTestSkipped('Legacy Twig tests are not applicable to this project');
    }
}
