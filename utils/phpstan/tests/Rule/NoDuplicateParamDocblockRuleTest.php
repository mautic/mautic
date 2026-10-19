<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoDuplicateParamDocblockRule;

/**
 * @extends RuleTestCase<NoDuplicateParamDocblockRule>
 */
final class NoDuplicateParamDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDuplicateParamDocblockRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/NoDuplicateParamDocblock/DuplicateParam.php'], [
            [
                'Param "$entity" is documented twice in the "createForm()" docblock, keep a single "@param" per parameter.',
                14,
            ],
        ]);
    }
}
