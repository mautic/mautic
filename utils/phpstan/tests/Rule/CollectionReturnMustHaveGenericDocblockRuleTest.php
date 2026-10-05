<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\CollectionReturnMustHaveGenericDocblockRule;

/**
 * @extends RuleTestCase<CollectionReturnMustHaveGenericDocblockRule>
 */
final class CollectionReturnMustHaveGenericDocblockRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CollectionReturnMustHaveGenericDocblockRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionReturn/BareCollectionReturnEntity.php'], [
            [
                'Method "getIpAddresses()" returns bare "Collection", add generic docblock, e.g. "@return Collection<int, SomeEntity>".',
                13,
            ],
            [
                'Method "getItems()" returns bare "Collection", add generic docblock, e.g. "@return Collection<int, SomeEntity>".',
                21,
            ],
        ]);
    }

    public function testSkipGenericDocblockAndNonCollection(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionReturn/SkipGenericCollectionReturnEntity.php'], []);
    }
}
