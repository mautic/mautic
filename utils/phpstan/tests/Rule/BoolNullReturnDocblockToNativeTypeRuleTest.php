<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Utils\PHPStan\Collector\BoolNullReturnDocblockCollector;
use Utils\PHPStan\Rule\BoolNullReturnDocblockToNativeTypeRule;

/**
 * @extends RuleTestCase<BoolNullReturnDocblockToNativeTypeRule>
 */
final class BoolNullReturnDocblockToNativeTypeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new BoolNullReturnDocblockToNativeTypeRule($this->createReflectionProvider());
    }

    /**
     * @return list<Collector>
     */
    protected function getCollectors(): array
    {
        return [new BoolNullReturnDocblockCollector(self::getContainer()->getByType(FileTypeMapper::class))];
    }

    public function testRule(): void
    {
        $this->analyse(glob(__DIR__.'/Fixture/BoolNullReturnDocblockToNativeType/*.php') ?: [], [
            [
                'Method "getIsPreferenceCenter()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "isActive()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "isActive()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "isActive()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "isShared()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "overridden()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                12,
            ],
            [
                'Method "isShortNullable()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                20,
            ],
            [
                'Method "overridden()" has a "bool|null" return docblock, so it must declare the native "?bool" return type instead.',
                52,
            ],
        ]);
    }
}
