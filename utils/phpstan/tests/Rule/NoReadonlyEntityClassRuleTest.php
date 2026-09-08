<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoReadonlyEntityClassRule;

/**
 * @extends RuleTestCase<NoReadonlyEntityClassRule>
 */
final class NoReadonlyEntityClassRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoReadonlyEntityClassRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ReadonlyEntity.php'], [
            [
                'Entity class "Utils\PHPStan\Tests\Rule\Fixture\ReadonlyEntity" must not be readonly. Doctrine hydrates entities via reflection without the constructor, which a readonly class forbids. Remove the readonly modifier from the class.',
                9,
            ],
        ]);
    }

    public function testSkipNonReadonlyEntity(): void
    {
        $this->analyse([__DIR__.'/Fixture/NonReadonlyEntity.php'], []);
    }

    public function testSkipReadonlyValueObjectWithoutMetadata(): void
    {
        $this->analyse([__DIR__.'/Fixture/ReadonlyValueObject.php'], []);
    }
}
