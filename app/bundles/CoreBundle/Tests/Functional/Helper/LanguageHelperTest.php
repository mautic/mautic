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
        $languageHelper = self::$container->get(LanguageHelper::class);
        \assert($languageHelper instanceof LanguageHelper);

        $languageFiles = $languageHelper->getLanguageFiles();

        // As the list depends on linstalled plugins, let's assert only for random files that should exist.
        Assert::assertStringEndsWith('app/bundles/EmailBundle/Translations/en_US/messages.ini', $languageFiles['EmailBundle'][0]);
        Assert::assertStringEndsWith('app/bundles/EmailBundle/Translations/en_US/flashes.ini', $languageFiles['EmailBundle'][1]);
        Assert::assertStringEndsWith('app/bundles/LeadBundle/Translations/en_US/flashes.ini', $languageFiles['LeadBundle'][1]);
    }
}
