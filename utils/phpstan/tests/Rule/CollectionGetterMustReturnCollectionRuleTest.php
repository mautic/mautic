<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use Doctrine\Common\Collections\Collection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\CollectionGetterMustReturnCollectionRule;

/**
 * @extends RuleTestCase<CollectionGetterMustReturnCollectionRule>
 */
final class CollectionGetterMustReturnCollectionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CollectionGetterMustReturnCollectionRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionGetter/UntypedCollectionGetterEntity.php'], [
            [
                sprintf('Method "getIpAddresses()" returns to-many association "$ipAddresses", so it must declare "%s" return type.', Collection::class),
                21,
            ],
            [
                sprintf('Method "getItems()" returns to-many association "$items", so it must declare "%s" return type.', Collection::class),
                26,
            ],
        ]);
    }

    public function testSkipTypedGetterAndNonCollectionProperty(): void
    {
        $this->analyse([__DIR__.'/Fixture/CollectionGetter/SkipTypedCollectionGetterEntity.php'], []);
    }
}
