<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ClassNameServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceDefinitionFetchCollector;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;
use Utils\PHPStan\Rule\NoClassNameServiceAliasRule;

/**
 * @extends RuleTestCase<NoClassNameServiceAliasRule>
 */
final class NoClassNameServiceAliasRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoClassNameServiceAliasRule();
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [
            new ClassNameServiceAliasCollector(),
            new ServiceDefinitionFetchCollector(),
            new ServiceDefinitionNameCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ClassNameAliasBundle/Config/services.php',
        ], [
            [
                'Service alias of "Utils\PHPStan\Tests\Rule\Fixture\ClassNameAliasBundle\SomeHelper" to "mautic.alias.some_helper" brings no value, register the service by its class name instead - $services->set(Utils\PHPStan\Tests\Rule\Fixture\ClassNameAliasBundle\SomeHelper::class).',
                13,
            ],
        ]);
    }
}
