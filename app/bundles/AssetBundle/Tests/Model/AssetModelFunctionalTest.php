<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Model;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class AssetModelFunctionalTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['assets']);
    }

    /**
     * @param array<string, string> $clickthrough
     */
    #[DataProvider('generateUrlDataProvider')]
    public function testGenerateUrl(
        string $assetAlias,
        bool $absolute,
        array $clickthrough,
        ?string $stream,
        string $expectedQuery,
    ): void {
        $asset = new Asset();
        $asset->setTitle($assetAlias);
        $asset->setAlias($assetAlias);
        $asset->setDateAdded(new \DateTime());
        $asset->setDateModified(new \DateTime());
        $asset->setCreatedByUser('User');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/remote/asset/'.$assetAlias);
        $asset->setSize(0);
        $asset->setIsPublished(true);

        $this->em->persist($asset);
        $this->em->flush();

        $this->assertNotNull($asset->getUuid());
        $slug = $asset->getSlug();

        $expectedUrl = 'https://localhost/asset/'.$slug.$expectedQuery;

        $assetModel = static::getContainer()->get('mautic.asset.model.asset');
        assert($assetModel instanceof AssetModel);
        $generatedUrl = $assetModel->generateUrl($asset, $absolute, $clickthrough, $stream);

        $this->assertSame($expectedUrl, $generatedUrl);
    }

    /**
     * @return iterable<string, array<int, bool|string|array<string, string>|null>>
     */
    public static function generateUrlDataProvider(): iterable
    {
        $clickThrough        = ['ct' => 'encoded-string'];
        $clickThroughEncoded = urlencode(base64_encode(serialize($clickThrough)));

        yield 'Absolute URL' => [
            'asset-to-download',
            true,
            [],
            null,
            '',
        ];

        yield 'Absolute URL with clickthrough' => [
            'asset-with-ct',
            true,
            ['ct' => 'encoded-string'],
            null,
            '?ct='.$clickThroughEncoded,
        ];

        yield 'Absolute URL with stream' => [
            'stream-asset',
            true,
            [],
            '1',
            '?stream=1',
        ];

        yield 'Absolute URL with stream and clickthrough' => [
            'stream-ct-asset',
            true,
            $clickThrough,
            '0',
            '?stream=0&ct='.$clickThroughEncoded,
        ];
    }

    public function testGenerateUrlWithAliasFallback(): void
    {
        $asset = new Asset();
        $asset->setTitle('asset-alias-fallback');
        $asset->setAlias('the-alias');
        $asset->setDateAdded(new \DateTime());
        $asset->setDateModified(new \DateTime());
        $asset->setCreatedByUser('User');
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/remote/asset/the-alias');
        $asset->setSize(0);
        $asset->setIsPublished(true);

        $this->em->persist($asset);
        $this->em->flush();

        // Set UUID to null in memory to test the fallback.
        $asset->setUuid(null);

        $this->assertNull($asset->getUuid());
        $this->assertSame('1:the-alias', $asset->getSlug());

        $assetModel = static::getContainer()->get('mautic.asset.model.asset');
        assert($assetModel instanceof AssetModel);
        $generatedUrl = $assetModel->generateUrl($asset, true, [], null);
        $this->assertSame('https://localhost/asset/1:the-alias', $generatedUrl);
    }
}
