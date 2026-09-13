<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\PreferCustomRepositoryOverGetRepositoryRule;

/**
 * @extends RuleTestCase<PreferCustomRepositoryOverGetRepositoryRule>
 */
final class PreferCustomRepositoryOverGetRepositoryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferCustomRepositoryOverGetRepositoryRule(
            self::getContainer()->getByType(ReflectionProvider::class)
        );
    }

    public function testRule(): void
    {
        $errorMessage = 'Entity "Utils\PHPStan\Tests\Rule\Fixture\EntityWithCustomRepository" declares the custom repository "Utils\PHPStan\Tests\Rule\Fixture\CustomEntityRepository". Inject and use that repository as a typed dependency instead of getRepository(), to make the dependency and its type explicit.';

        $this->analyse([__DIR__.'/Fixture/GetRepositoryOnCustomRepositoryEntityService.php'], [
            [$errorMessage, 20],
            [$errorMessage, 25],
        ]);
    }
}
