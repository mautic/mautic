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
        $languageHelper = self::getContainer()->get(LanguageHelper::class);
        $this->assertInstanceOf(LanguageHelper::class, $languageHelper);

        $languageFiles = $languageHelper->getLanguageFiles();

        // As the list depends on installed plugins, let's assert only for random files that should exist.
        $this->assertBundleContainsDefaultLanguageFile($languageFiles, 'EmailBundle');
        $this->assertBundleContainsDefaultLanguageFile($languageFiles, 'LeadBundle');
    }

    /**
     * @param array<string, string[]> $languageFiles
     */
    private function assertBundleContainsDefaultLanguageFile(array $languageFiles, string $bundle): void
    {
        $this->assertArrayHasKey($bundle, $languageFiles);
        $this->assertNotEmpty(array_filter(
            $languageFiles[$bundle],
            static fn (string $file): bool => 1 === preg_match(
                sprintf('/app\/bundles\/%s\/Translations\/en_US\/(messages|validators|flashes|javascript)\.ini/', $bundle),
                $file
            )
        ));
    }
}
