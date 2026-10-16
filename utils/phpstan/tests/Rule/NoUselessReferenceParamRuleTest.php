<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Collector\ByReferenceParamCollector;
use Utils\PHPStan\Rule\NoUselessReferenceParamRule;

/**
 * @extends RuleTestCase<NoUselessReferenceParamRule>
 */
final class NoUselessReferenceParamRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUselessReferenceParamRule($this->createReflectionProvider());
    }

    /**
     * @return list<Collector>
     */
    protected function getCollectors(): array
    {
        return [new ByReferenceParamCollector($this->createReflectionProvider())];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UselessReferenceParam.php'], [
            [
                'Parameter "$entity" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                9,
            ],
            [
                'Parameter "$payload" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                17,
            ],
        ]);
    }

    public function testSkipNeededReference(): void
    {
        $this->analyse([__DIR__.'/Fixture/NeededReferenceParam.php'], []);
    }

    public function testSkipOverridingMethodWhoseRootIsOutsideTheAnalysis(): void
    {
        $this->analyse([__DIR__.'/Fixture/OverridingReferenceParam.php'], []);
    }

    public function testReportsOnlyOnFamilyRoot(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ReferenceFamily/DispatchRoot.php',
            __DIR__.'/Fixture/ReferenceFamily/ReadingDispatchChild.php',
        ], [
            [
                'Parameter "$entity" is passed by reference but never written to, so the reference has no effect. Remove the "&".',
                9,
            ],
        ]);
    }

    public function testSkipFamilyWhenAnOverrideWrites(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ReferenceFamily/DispatchRoot.php',
            __DIR__.'/Fixture/ReferenceFamily/WritingDispatchChild.php',
        ], []);
    }
}
