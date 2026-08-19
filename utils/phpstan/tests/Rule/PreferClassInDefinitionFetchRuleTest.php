<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ClassNameServiceAliasCollector;
use Utils\PHPStan\Collector\ClassTargetServiceAliasCollector;
use Utils\PHPStan\Collector\DefinitionFetchByStringCollector;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;
use Utils\PHPStan\Rule\PreferClassInDefinitionFetchRule;

/**
 * @extends RuleTestCase<PreferClassInDefinitionFetchRule>
 */
final class PreferClassInDefinitionFetchRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferClassInDefinitionFetchRule();
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [
            new DefinitionFetchByStringCollector(),
            new ServiceDefinitionNameCollector(),
            new ClassTargetServiceAliasCollector(),
            new ClassNameServiceAliasCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/DefinitionFetchBundle/Config/services.php',
            __DIR__.'/Fixture/DefinitionFetchBundle/SomePass.php',
            __DIR__.'/Fixture/DefinitionFetchBundle/StringHelper.php',
            __DIR__.'/Fixture/DefinitionFetchBundle/AliasedHelper.php',
        ], [
            [
                'Fetch the definition by its class, getDefinition(Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchBundle\StringHelper::class), rather than by the string id "mautic.string.helper" the service is registered with.',
                14,
            ],
            [
                'Fetch the definition by its class, getDefinition(Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchBundle\AliasedHelper::class), rather than by the string id "mautic.aliased.helper" the service is registered with.',
                15,
            ],
        ]);
    }
}
