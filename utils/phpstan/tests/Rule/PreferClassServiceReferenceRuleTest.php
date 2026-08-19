<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ClassTargetServiceAliasCollector;
use Utils\PHPStan\Collector\ServiceStringReferenceCollector;
use Utils\PHPStan\Rule\PreferClassServiceReferenceRule;

/**
 * @extends RuleTestCase<PreferClassServiceReferenceRule>
 */
final class PreferClassServiceReferenceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferClassServiceReferenceRule();
    }

    /**
     * @return list<Collector<\PhpParser\Node, mixed>>
     */
    protected function getCollectors(): array
    {
        return [
            new ClassTargetServiceAliasCollector(),
            new ServiceStringReferenceCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ClassServiceReferenceBundle/Config/services.php',
            __DIR__.'/Fixture/ClassServiceReferenceBundle/SomeHelper.php',
            __DIR__.'/Fixture/ClassServiceReferenceBundle/DependentService.php',
        ], [
            [
                'Reference the service by its class, service(Utils\PHPStan\Tests\Rule\Fixture\ClassServiceReferenceBundle\SomeHelper::class), rather than by the string id "mautic.some.helper" a class name alias already covers.',
                19,
            ],
        ]);
    }
}
