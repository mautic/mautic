<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Entity;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Test\ReflectionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    public function testGetSlugReturnsUuidWhenPresent(): void
    {
        $asset = new Asset();

        ReflectionHelper::setValue($asset, 'id', 10);
        $asset->setUuid('123e4567-e89b-12d3-a456-426614174000');

        $this->assertSame(
            '123e4567-e89b-12d3-a456-426614174000',
            $asset->getSlug()
        );
    }

    public function testGetSlugFallsBackToIdAndAlias(): void
    {
        $asset = new Asset();

        ReflectionHelper::setValue($asset, 'id', 10);
        $asset->setAlias('my-alias');

        $this->assertSame('10:my-alias', $asset->getSlug());
    }

    public function testGetSlugAllowsNullAlias(): void
    {
        $asset = new Asset();

        ReflectionHelper::setValue($asset, 'id', 10);
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

    /**
     * @return iterable<string, array{string}>
     */
    public static function dataPathsOutsideTheUploadDir(): iterable
    {
        yield 'parent traversal' => ['../../../../../../../../etc/passwd'];
        yield 'traversal into the app config' => ['../../config/local.php'];
        yield 'traversal hidden behind a subdir' => ['subdir/../../../../etc/passwd'];
        yield 'escaping to a sibling directory' => ['../files-backup/dump.sql'];
        yield 'the parent itself' => ['..'];
        yield 'the upload dir itself' => ['.'];
        yield 'empty path' => [''];
        yield 'null byte' => ["../../etc/passwd\0.txt"];
    }

    #[DataProvider('dataPathsOutsideTheUploadDir')]
    public function testGetAbsolutePathRefusesPathOutsideUploadDir(string $path): void
    {
        $asset = new Asset();
        $asset->setUploadDir('/var/www/html/media/files');
        $asset->setPath($path);

        $this->assertNull($asset->getAbsolutePath());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function dataPathsInsideTheUploadDir(): iterable
    {
        yield 'plain file name' => ['report.pdf', '/var/www/html/media/files/report.pdf'];
        yield 'nested file' => ['nested/dir/report.pdf', '/var/www/html/media/files/nested/dir/report.pdf'];
        yield 'traversal resolving back inside' => ['nested/../report.pdf', '/var/www/html/media/files/report.pdf'];
    }

    #[DataProvider('dataPathsInsideTheUploadDir')]
    public function testGetAbsolutePathResolvesPathInsideUploadDir(string $path, string $expected): void
    {
        $asset = new Asset();
        $asset->setUploadDir('/var/www/html/media/files');
        $asset->setPath($path);

        $this->assertSame($expected, $asset->getAbsolutePath());
    }

    public function testGetAbsolutePathIsNullWhenPathIsNotSet(): void
    {
        $asset = new Asset();
        $asset->setUploadDir('/var/www/html/media/files');

        $this->assertNull($asset->getAbsolutePath());
    }
}
