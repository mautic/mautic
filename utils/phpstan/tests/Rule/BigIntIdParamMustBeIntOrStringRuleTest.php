<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\BigIntIdParamMustBeIntOrStringRule;

/**
 * @extends RuleTestCase<BigIntIdParamMustBeIntOrStringRule>
 */
final class BigIntIdParamMustBeIntOrStringRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new BigIntIdParamMustBeIntOrStringRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/BigIntIdParam/BigIntIdEntityRepository.php'], [
            [
                'Param "$id" of "exists()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                14,
            ],
            [
                'Param "$id" of "findByStringId()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                19,
            ],
        ]);
    }

    public function testSkipNonBigIntIdEntity(): void
    {
        $this->analyse([__DIR__.'/Fixture/BigIntIdParam/IntIdEntityRepository.php'], []);
    }
}
