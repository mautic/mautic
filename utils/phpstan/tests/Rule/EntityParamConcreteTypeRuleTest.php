<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Rule\EntityParamConcreteTypeRule;

/**
 * @extends RuleTestCase<EntityParamConcreteTypeRule>
 */
final class EntityParamConcreteTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new EntityParamConcreteTypeRule(
            self::getContainer()->getByType(FileTypeMapper::class)
        );
    }

    public function testRule(): void
    {
        $errorMessage = 'Param "$%s" of "%s()" must declare the concrete entity class, not just "object". When a parent contract forces "object", narrow it with a "@param" tag.';

        $this->analyse([__DIR__.'/Fixture/EntityParamConcreteType/SomeModel.php'], [
            [
                sprintf($errorMessage, 'entity', 'bareObject'),
                9,
            ],
            [
                sprintf($errorMessage, 'entity', 'unionWithoutClass'),
                13,
            ],
            [
                sprintf($errorMessage, 'entity', 'untyped'),
                17,
            ],
        ]);
    }

    public function testRepository(): void
    {
        $errorMessage = 'Param "$%s" of "%s()" must declare the concrete entity class, not just "object". When a parent contract forces "object", narrow it with a "@param" tag.';

        $this->analyse([__DIR__.'/Fixture/EntityParamConcreteType/SomeRepository.php'], [
            [
                sprintf($errorMessage, 'entity', 'bareObject'),
                9,
            ],
        ]);
    }

    public function testNonModelOrRepositoryClassIsSkipped(): void
    {
        $this->analyse([__DIR__.'/Fixture/EntityParamConcreteType/PlainService.php'], []);
    }
}
