<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\GenericTraitUseMustHaveGenericDocblockRule;

/**
 * @extends RuleTestCase<GenericTraitUseMustHaveGenericDocblockRule>
 */
final class GenericTraitUseMustHaveGenericDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new GenericTraitUseMustHaveGenericDocblockRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/GenericTraitUse/MissingUseDocblock.php'], [
            [
                'Trait "SomeGenericTrait" is generic, add "@use SomeGenericTrait<SomeEntity>" docblock above the "use" statement.',
                9,
            ],
        ]);
    }

    public function testSkipDocumentedAndPlainTraitUse(): void
    {
        $this->analyse([__DIR__.'/Fixture/GenericTraitUse/SkipDocumentedAndPlainTraitUse.php'], []);
    }
}
