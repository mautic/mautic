<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Helper;

use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

final class LanguageHelperTest extends MauticMysqlTestCase
{
    public function testGettingLanguageFiles(): void
    {
        $languageHelper = static::getContainer()->get(LanguageHelper::class);
        \assert($languageHelper instanceof LanguageHelper);

        $languageFiles = $languageHelper->getLanguageFiles();

        // As the list depends on installed plugins, let's assert only for random files that should exist.
        self::assertBundleContainsDefaultLanguageFile($languageFiles, 'EmailBundle');
        self::assertBundleContainsDefaultLanguageFile($languageFiles, 'LeadBundle');
    }

    /**
     * @param array<string, string[]> $languageFiles
     */
    private static function assertBundleContainsDefaultLanguageFile(array $languageFiles, string $bundle): void
    {
        Assert::assertArrayHasKey($bundle, $languageFiles);
        Assert::assertNotEmpty(
            array_filter(
                $languageFiles[$bundle],
                static fn (string $file): bool => 1 === preg_match(
                    sprintf('/app\/bundles\/%s\/Translations\/en_US\/(messages|validators|flashes)\.ini/', $bundle),
                    $file
                )
            )
        );
    }
}
