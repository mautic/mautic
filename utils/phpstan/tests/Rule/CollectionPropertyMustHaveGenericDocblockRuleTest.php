<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\CollectionPropertyMustHaveGenericDocblockRule;

/**
 * @extends RuleTestCase<CollectionPropertyMustHaveGenericDocblockRule>
 */
final class CollectionPropertyMustHaveGenericDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CollectionPropertyMustHaveGenericDocblockRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionProperty/BareCollectionProperty.php'], [
            [
                'Property "$items" is bare "Collection", add generic docblock, e.g. "@var Collection<int, SomeEntity>".',
                11,
            ],
            [
                'Property "$docblockedItems" is bare "Collection", add generic docblock, e.g. "@var Collection<int, SomeEntity>".',
                16,
            ],
        ]);
    }

    public function testSkipGenericPropertyAndNonCollection(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionProperty/SkipGenericCollectionProperty.php'], []);
    }
}
