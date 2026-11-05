<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\ControllerBigIntIdParamByNameRule;

/**
 * @extends RuleTestCase<ControllerBigIntIdParamByNameRule>
 */
final class ControllerBigIntIdParamByNameRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ControllerBigIntIdParamByNameRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ControllerBigIntIdParamByName/SomeController.php'], [
            [
                'Param "$leadId" of "indexAction()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                9,
            ],
            [
                'Param "$contactId" of "nullableAction()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                13,
            ],
            [
                'Param "$contactId" of "filterAction()" must be "int|string", as the entity id is unsigned bigint hydrated as string.',
                17,
            ],
        ]);
    }

    public function testSkipNonController(): void
    {
        $this->analyse([__DIR__.'/Fixture/ControllerBigIntIdParamByName/SomeModel.php'], []);
    }
}
