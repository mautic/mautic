<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Functional\Entity;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\Entity\AssetRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class AssetRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private AssetRepository $assetRepository;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var AssetRepository $repository */
        $repository            = $this->em->getRepository(Asset::class);
        $this->assetRepository = $repository;

        $this->createAsset('Photo', 'image/png');
        $this->createAsset('Clip', 'video/mp4');
        $this->createAsset('Song', 'audio/mpeg');
        $this->createAsset('Report', 'application/pdf');
        $this->createAsset('Notes', 'text/plain');
        $this->createAsset('Archive', 'application/zip');
        $this->createAsset('Unknown', null);
        $this->em->flush();
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function mimeCommandProvider(): iterable
    {
        yield 'image' => ['is:image', ['Photo']];
        yield 'video' => ['is:video', ['Clip']];
        yield 'audio' => ['is:audio', ['Song']];
        yield 'document' => ['is:document', ['Notes', 'Report']];
    }

    /**
     * @param string[] $expectedTitles
     */
    #[DataProvider('mimeCommandProvider')]
    public function testMimeCommandReturnsOnlyMatchingAssets(string $searchString, array $expectedTitles): void
    {
        $this->assertSame($expectedTitles, $this->getTitles($searchString));
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function negatedMimeCommandProvider(): iterable
    {
        yield 'image' => ['!is:image', ['Archive', 'Clip', 'Notes', 'Report', 'Song', 'Unknown']];
        yield 'document' => ['!is:document', ['Archive', 'Clip', 'Photo', 'Song', 'Unknown']];
    }

    /**
     * @param string[] $expectedTitles
     */
    #[DataProvider('negatedMimeCommandProvider')]
    public function testNegatedMimeCommandKeepsAssetsWithoutMime(string $searchString, array $expectedTitles): void
    {
        $this->assertSame($expectedTitles, $this->getTitles($searchString));
    }

    /**
     * @return string[]
     */
    private function getTitles(string $searchString): array
    {
        $assets = $this->assetRepository->getEntities([
            'filter'           => ['string' => $searchString, 'force' => []],
            'ignore_paginator' => true,
        ]);
        $titles = array_map(
            static fn (Asset $asset): string => $asset->getTitle(),
            is_array($assets) ? $assets : iterator_to_array($assets)
        );
        sort($titles);

        return $titles;
    }

    private function createAsset(string $title, ?string $mime): void
    {
        $asset = new Asset();
        $asset->setTitle($title);
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/'.strtolower($title));
        $asset->setMime($mime);
        $this->em->persist($asset);
    }
}
