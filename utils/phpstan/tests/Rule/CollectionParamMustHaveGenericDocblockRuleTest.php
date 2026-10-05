<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\CollectionParamMustHaveGenericDocblockRule;

/**
 * @extends RuleTestCase<CollectionParamMustHaveGenericDocblockRule>
 */
final class CollectionParamMustHaveGenericDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CollectionParamMustHaveGenericDocblockRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionParam/BareCollectionParam.php'], [
            [
                'Parameter "$items" of method "setItems()" is bare "Collection", add generic docblock, e.g. "@param Collection<int, SomeEntity> $items".',
                11,
            ],
            [
                'Parameter "$items" of method "setDocblockedItems()" is bare "Collection", add generic docblock, e.g. "@param Collection<int, SomeEntity> $items".',
                18,
            ],
        ]);
    }

    public function testSkipGenericParamAndNonCollection(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionParam/SkipGenericCollectionParam.php'], []);
    }
}
