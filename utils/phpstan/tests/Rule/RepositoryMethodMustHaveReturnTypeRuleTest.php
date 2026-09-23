<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\RepositoryMethodMustHaveReturnTypeRule;

/**
 * @extends RuleTestCase<RepositoryMethodMustHaveReturnTypeRule>
 */
final class RepositoryMethodMustHaveReturnTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RepositoryMethodMustHaveReturnTypeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/UntypedReturnRepository.php'], [
            [
                'Public method "findActive()" of repository class must declare a return type.',
                9,
            ],
        ]);
    }

    public function testSkipNonRepositoryClass(): void
    {
        $this->analyse([__DIR__.'/Fixture/SomeNonRepositoryService.php'], []);
    }
}
