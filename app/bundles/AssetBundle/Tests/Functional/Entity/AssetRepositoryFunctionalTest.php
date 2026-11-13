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

        $this->createAsset('Photo', 'image/png', 'png');
        $this->createAsset('Clip', 'video/mp4', 'mp4');
        $this->createAsset('Song', 'audio/mpeg', 'mp3');
        $this->createAsset('Report', 'application/pdf', 'pdf');
        $this->createAsset('Notes', 'text/plain', 'txt');
        $this->createAsset('Archive', 'application/zip', 'zip');
        $this->createAsset('Unknown', null, null);
        $this->createAsset('Layers', 'application/x-photoshop', 'psd');
        $this->createAsset('Scanned', null, 'jpg');
        $this->createAsset('Octet photo', 'application/octet-stream', 'png');
        $this->createAsset('Generic zip', 'application/octet-stream', 'zip');
        $this->createAsset('Charset notes', 'text/plain; charset=utf-8', 'txt');
        $this->createAsset('Macro sheet', 'application/vnd.ms-excel.sheet.macroEnabled.12', 'xlsm');
        $this->em->flush();
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function mimeCommandProvider(): iterable
    {
        yield 'image' => ['is:image', ['Layers', 'Octet photo', 'Photo', 'Scanned']];
        yield 'video' => ['is:video', ['Clip']];
        yield 'audio' => ['is:audio', ['Song']];
        yield 'document' => ['is:document', ['Charset notes', 'Macro sheet', 'Notes', 'Report']];
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
        yield 'image' => ['!is:image', ['Archive', 'Charset notes', 'Clip', 'Generic zip', 'Macro sheet', 'Notes', 'Report', 'Song', 'Unknown']];
        yield 'document' => ['!is:document', ['Archive', 'Clip', 'Generic zip', 'Layers', 'Octet photo', 'Photo', 'Scanned', 'Song', 'Unknown']];
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
        /** @var Asset[] $assets */
        $assets = $this->assetRepository->getEntities([
            'filter'           => ['string' => $searchString, 'force' => []],
            'ignore_paginator' => true,
        ]);
        $titles = array_map(
            static fn (Asset $asset): string => $asset->getTitle(),
            $assets
        );
        sort($titles);

        return $titles;
    }

    private function createAsset(string $title, ?string $mime, ?string $extension): void
    {
        $asset = new Asset();
        $asset->setTitle($title);
        $asset->setStorageLocation('remote');
        $asset->setRemotePath('https://example.com/'.strtolower($title));
        $asset->setMime($mime);
        $asset->setExtension($extension);
        $this->em->persist($asset);
    }
}
