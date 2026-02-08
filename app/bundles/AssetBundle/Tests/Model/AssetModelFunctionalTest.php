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
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    #[DataProvider('generateUrlDataProvider')]
    public function testGenerateUrl(
        string $assetAlias,
        bool $absolute,
        array $clickthrough,
        ?string $stream,
        string $expectedUrl,
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
            'https://localhost/asset/1:asset-to-download',
        ];

        yield 'Absolute URL with clickthrough' => [
            'asset-with-ct',
            true,
            ['ct' => 'encoded-string'],
            null,
            'https://localhost/asset/1:asset-with-ct?ct='.$clickThroughEncoded,
        ];

        yield 'Absolute URL with stream' => [
            'stream-asset',
            true,
            [],
            '1',
            'https://localhost/asset/1:stream-asset?stream=1',
        ];

        yield 'Absolute URL with stream and clickthrough' => [
            'stream-ct-asset',
            true,
            $clickThrough,
            '0',
            'https://localhost/asset/1:stream-ct-asset?stream=0&ct='.$clickThroughEncoded,
        ];
    }
}
