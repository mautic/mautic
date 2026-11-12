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
    private const string ERROR_MESSAGE = 'Static "loadMetadata()" method is not allowed. Map the entity with Doctrine #[ORM\...] attributes instead.';

    protected function getRule(): Rule
    {
        return new NoLoadMetadataMethodRule();
    }

    public function testEntity(): void
    {
        $this->analyse([__DIR__.'/Fixture/NoLoadMetadata/EntityWithLoadMetadata.php'], [
            [self::ERROR_MESSAGE, 11],
        ]);
    }

    public function testTrait(): void
    {
        $this->analyse([__DIR__.'/Fixture/NoLoadMetadata/ClassUsingTrait.php', __DIR__.'/Fixture/NoLoadMetadata/TraitWithLoadMetadata.php'], [
            [self::ERROR_MESSAGE, 11],
        ]);
    }

    public function testSkipNonStaticDriverMethod(): void
    {
        $this->analyse([__DIR__.'/Fixture/NoLoadMetadata/MetadataDriver.php'], []);
    }
}
