<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Utils\PHPStan\Rule\NoLoadMetadataMethodRule;

/**
 * @extends RuleTestCase<NoLoadMetadataMethodRule>
 */
final class NoLoadMetadataMethodRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoLoadMetadataMethodRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Fixture/StaticPhpMappedEntity.php'], [
            [
                'Entity must map via Doctrine attributes, not a static loadMetadata() method. Convert the mapping to #[ORM\*] attributes and remove this method.',
                11,
            ],
        ]);
    }

    public function testSkipNonStaticLoadMetadata(): void
    {
        $this->analyse([__DIR__.'/Fixture/AttributeMappedEntity.php'], []);
    }
}
