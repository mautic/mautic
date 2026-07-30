<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceModelKeyUsageCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;
use Utils\PHPStan\Rule\NoUnusedServiceAliasRule;

/**
 * @extends RuleTestCase<NoUnusedServiceAliasRule>
 */
final class NoUnusedServiceAliasRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnusedServiceAliasRule();
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [
            new ServiceAliasCollector(),
            new ServiceModelKeyUsageCollector(),
            new ServiceNameUsageCollector(),
        ];
    }

    public function testRule(): void
    {
        $servicesFilePath = __DIR__.'/Fixture/AliasBundle/Config/services.php';

        $this->analyse([
            $servicesFilePath,
            __DIR__.'/Fixture/AliasBundle/Config/config.php',
            __DIR__.'/Fixture/AliasBundle/AliasHelperFetcher.php',
            __DIR__.'/Fixture/AliasBundle/UsedAliasHelper.php',
            __DIR__.'/Fixture/AliasBundle/UnusedAliasHelper.php',
        ], [
            [
                'Service alias "mautic.alias.model.not_a_model" is never used, remove it.',
                21,
            ],
            [
                'Service alias "mautic.alias.legacy_unused_helper" is never used, remove it.',
                25,
            ],
        ]);
    }
}
