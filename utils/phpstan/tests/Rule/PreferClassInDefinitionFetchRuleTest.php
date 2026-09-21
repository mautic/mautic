<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\PreferClassInDefinitionFetchRule;

/**
 * @extends RuleTestCase<PreferClassInDefinitionFetchRule>
 */
final class PreferClassInDefinitionFetchRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferClassInDefinitionFetchRule($this->createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/DefinitionFetchClassConst/SomePass.php'], [
            [
                'Fetch the definition by class constant, Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchClassConst\SomeHelper::class, rather than the string "Utils\PHPStan\Tests\Rule\Fixture\DefinitionFetchClassConst\SomeHelper".',
                16,
            ],
        ]);
    }
}
