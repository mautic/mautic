<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Entity;

use Mautic\AssetBundle\Entity\Asset;
use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    public function testGetSlugReturnsUuidWhenPresent(): void
    {
        $asset = new Asset();

        $this->setEntityId($asset);
        $asset->setUuid('123e4567-e89b-12d3-a456-426614174000');

        $this->assertSame(
            '123e4567-e89b-12d3-a456-426614174000',
            $asset->getSlug()
        );
    }

    public function testGetSlugFallsBackToIdAndAlias(): void
    {
        $asset = new Asset();

        $this->setEntityId($asset);
        $asset->setAlias('my-alias');

        $this->assertSame('10:my-alias', $asset->getSlug());
    }

    public function testGetSlugAllowsNullAlias(): void
    {
        $asset = new Asset();

        $this->setEntityId($asset);
        $asset->setAlias(null);

        $this->assertSame('10:', $asset->getSlug());
    }

    public function testGetSlugThrowsExceptionWhenIdIsMissing(): void
    {
        $asset = new Asset();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'This asset must be saved before it can be used in a URL.'
        );

        $asset->getSlug();
    }

    private function setEntityId(object $object): void
    {
        $reflection = new \ReflectionProperty($object, 'id');
        $reflection->setValue($object, 10);
    }
}
