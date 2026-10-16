<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Collector\ObjectParamDocblockCollector;
use Utils\PHPStan\Rule\MoveObjectParamDocblockToNativeTypeRule;

/**
 * @extends RuleTestCase<MoveObjectParamDocblockToNativeTypeRule>
 */
final class MoveObjectParamDocblockToNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new MoveObjectParamDocblockToNativeTypeRule($this->createReflectionProvider());
    }

    /**
     * @return list<Collector>
     */
    protected function getCollectors(): array
    {
        return [new ObjectParamDocblockCollector(self::getContainer()->getByType(FileTypeMapper::class))];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/ObjectParamDocblock.php'], [
            [
                'Parameter "$item" has an "@param Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject" docblock. Declare "Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject" as a native type and drop the "@param" tag.',
                12,
            ],
            [
                'Parameter "$item" has an "@param Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject" docblock. Declare "Utils\PHPStan\Tests\Rule\Fixture\SomeValueObject" as a native type and drop the "@param" tag.',
                20,
            ],
        ]);
    }

    public function testSkipOverrideFamily(): void
    {
        $this->analyse([
            __DIR__.'/Fixture/ParamFamily/BaseWithObjectParam.php',
            __DIR__.'/Fixture/ParamFamily/ChildOverridingObjectParam.php',
        ], []);
    }
}
