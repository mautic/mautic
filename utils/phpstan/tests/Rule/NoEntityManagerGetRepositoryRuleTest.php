<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoEntityManagerGetRepositoryRule;

/**
 * @extends RuleTestCase<NoEntityManagerGetRepositoryRule>
 */
final class NoEntityManagerGetRepositoryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoEntityManagerGetRepositoryRule();
    }

    public function testRule(): void
    {
        $errorMessage = 'Entity manager must not fetch the "stdClass" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.';

        $this->analyse([__DIR__.'/Fixture/GetRepositoryOnEntityManagerService.php'], [
            [$errorMessage, 18],
            [$errorMessage, 23],
        ]);
    }
}
