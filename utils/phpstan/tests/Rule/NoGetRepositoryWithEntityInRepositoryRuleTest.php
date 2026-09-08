<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoGetRepositoryWithEntityInRepositoryRule;

/**
 * @extends RuleTestCase<NoGetRepositoryWithEntityInRepositoryRule>
 */
final class NoGetRepositoryWithEntityInRepositoryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoGetRepositoryWithEntityInRepositoryRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/GetRepositoryByEntityRepository.php'], [
            [
                'Repository must not fetch the "stdClass" repository by entity constant. Inject the repository as a typed property instead, to make the dependency and its type explicit.',
                17,
            ],
        ]);

        $this->analyse([__DIR__.'/Fixture/GetRepositoryByEntityService.php'], []);
    }
}
