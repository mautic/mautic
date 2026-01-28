<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Twig;

/**
 * Trait to provide PHPUnit 10 compatibility for Twig integration tests
 * This handles the static data provider requirements and legacy test overrides.
 */
trait TwigIntegrationTestTrait
{
    /**
     * Static data provider for integration tests
     * This uses a helper method to work around PHPUnit 10's static requirements.
     *
     * @return iterable<array{string, string, string, array<string, string>, string|false, array<array{string|null, string, string|null, string}>, string}>
     */
    public static function integrationTestDataProvider(): iterable
    {
        return static::getIntegrationTestData();
    }

    /**
     * Get the fixtures directory for the test
     * Uses the directory of the class that uses this trait.
     */
    public static function getFixturesDirectory(): string
    {
        // Get the directory of the class that uses this trait
        $reflection = new \ReflectionClass(static::class);

        return dirname($reflection->getFileName()).'/Fixtures/';
    }

    /**
     * Helper method to get integration test data
     * This creates a temporary instance to call the parent's non-static methods.
     *
     * @return iterable<array{string, string, string, array<string, string>, string|false, array<array{string|null, string, string|null, string}>, string}>
     */
    protected static function getIntegrationTestData(): iterable
    {
        // Create a temporary instance of the actual test class
        $reflection = new \ReflectionClass(static::class);
        $instance   = $reflection->newInstanceWithoutConstructor();

        // Call the parent's getTests method
        return $instance->getTests('testIntegration', false);
    }

    /**
     * @dataProvider integrationTestDataProvider
     *
     * @param string                $file
     * @param string                $message
     * @param string                $condition
     * @param array<string, string> $templates
     * @param string|bool           $exception
     * @param array<mixed>          $outputs
     * @param string                $deprecation
     */
    public function testIntegration($file, $message, $condition, $templates, $exception, $outputs, $deprecation = ''): void
    {
        $this->doIntegrationTest($file, $message, $condition, $templates, $exception, $outputs, $deprecation);
    }

    /**
     * Override the legacy integration test to prevent it from running
     * We don't use legacy Twig features, so this test is not needed.
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
