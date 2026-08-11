<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ServiceDefinitionNameCollector;
use Utils\PHPStan\Collector\ServiceNameUsageCollector;
use Utils\PHPStan\Rule\NoUnusedServiceNameRule;
use Utils\PHPStan\ServiceNameUsageResolver;

/**
 * @extends RuleTestCase<NoUnusedServiceNameRule>
 */
final class NoUnusedServiceNameRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUnusedServiceNameRule(new ServiceNameUsageResolver());
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [
            new ServiceDefinitionNameCollector(),
            new ServiceNameUsageCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ServiceNameBundle/Config/services.php',
        ], [
            [
                'Service name "mautic.name.unused_service" is never used, register the service by its class name instead - $services->set(Utils\PHPStan\Tests\Rule\Fixture\ServiceNameBundle\UnusedNameService::class).',
                21,
            ],
        ]);
    }
}
