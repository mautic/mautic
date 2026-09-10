<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoStringTargetEntityRule;

/**
 * @extends RuleTestCase<NoStringTargetEntityRule>
 */
final class NoStringTargetEntityRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoStringTargetEntityRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/StringTargetEntityAssociation.php'], [
            [
                'Doctrine association #[ManyToOne] uses a string targetEntity "Tweet"; use Tweet::class instead.',
                11,
            ],
            [
                'Doctrine association #[OneToMany] uses a string targetEntity "Child"; use Child::class instead.',
                17,
            ],
        ]);
    }
}
