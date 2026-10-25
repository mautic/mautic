<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\EntityCollectionParamMustUseInterfaceRule;

/**
 * @extends RuleTestCase<EntityCollectionParamMustUseInterfaceRule>
 */
final class EntityCollectionParamMustUseInterfaceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new EntityCollectionParamMustUseInterfaceRule();
    }

    public function testRule(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/Entity/EntityCollectionParam.php',
            __DIR__.'/Fixture/NonEntityCollectionParam.php',
        ], [
            [
                'Param "$items" is typed as "ArrayCollection". Type it against the "Doctrine\Common\Collections\Collection" interface instead.',
                12,
            ],
        ]);
    }
}
