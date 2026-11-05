<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoGetRepositoryWithEntityRule;

/**
 * @extends RuleTestCase<NoGetRepositoryWithEntityRule>
 */
final class NoGetRepositoryWithEntityRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoGetRepositoryWithEntityRule(
            self::getContainer()->getByType(ReflectionProvider::class)
        );
    }

    public function testEntityManagerCaller(): void
    {
        $errorMessage = 'Do not fetch the "stdClass" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.';

        $this->analyse([__DIR__.'/Fixture/GetRepositoryOnEntityManagerService.php'], [
            [$errorMessage, 18],
            [$errorMessage, 23],
        ]);
    }

    public function testInsideRepository(): void
    {
        $errorMessage = 'Do not fetch the "stdClass" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.';

        $this->analyse([__DIR__.'/Fixture/GetRepositoryByEntityRepository.php'], [
            [$errorMessage, 17],
        ]);

        $this->analyse([__DIR__.'/Fixture/GetRepositoryByEntityService.php'], []);
    }

    public function testManagerRegistryWithCustomRepository(): void
    {
        $errorMessage = 'Do not fetch the "Utils\PHPStan\Tests\Rule\Fixture\EntityWithCustomRepository" repository by entity constant. Inject the repository as a typed dependency instead, to make the dependency and its type explicit.';

        $this->analyse([__DIR__.'/Fixture/GetRepositoryOnCustomRepositoryEntityService.php'], [
            [$errorMessage, 20],
            [$errorMessage, 25],
        ]);
    }
}
