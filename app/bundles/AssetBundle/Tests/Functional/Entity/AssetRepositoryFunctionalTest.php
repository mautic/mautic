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
        $this->createAsset('Generic doc', 'application/octet-stream', 'pdf');
        $this->createAsset('Blank doc', '', 'docx');
        $this->createAsset('Photoshop alias', 'application/photoshop', 'psd');
        $this->createAsset('Generic clip', 'application/octet-stream', 'mp4');
        $this->createAsset('Generic song', null, 'mp3');
        $this->createAsset('Generic icon', 'application/octet-stream', 'ico');
        $this->createAsset('Generic widescreen', 'application/octet-stream', 'wmv');
        $this->createAsset('Generic movie', null, 'mkv');
        $this->createAsset('Mislabeled clip', 'application/zip', 'mp4');
        $this->em->flush();
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function mimeCommandProvider(): iterable
    {
        yield 'image' => ['is:image', ['Generic icon', 'Layers', 'Octet photo', 'Photo', 'Photoshop alias', 'Scanned']];
        yield 'video' => ['is:video', ['Clip', 'Generic clip', 'Generic movie', 'Generic widescreen']];
        yield 'audio' => ['is:audio', ['Generic song', 'Song']];
        yield 'document' => ['is:document', ['Blank doc', 'Charset notes', 'Generic doc', 'Macro sheet', 'Notes', 'Report']];
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
        yield 'image' => ['!is:image', ['Archive', 'Blank doc', 'Charset notes', 'Clip', 'Generic clip', 'Generic doc', 'Generic movie', 'Generic song', 'Generic widescreen', 'Generic zip', 'Macro sheet', 'Mislabeled clip', 'Notes', 'Report', 'Song', 'Unknown']];
        yield 'video' => ['!is:video', ['Archive', 'Blank doc', 'Charset notes', 'Generic doc', 'Generic icon', 'Generic song', 'Generic zip', 'Layers', 'Macro sheet', 'Mislabeled clip', 'Notes', 'Octet photo', 'Photo', 'Photoshop alias', 'Report', 'Scanned', 'Song', 'Unknown']];
        yield 'audio' => ['!is:audio', ['Archive', 'Blank doc', 'Charset notes', 'Clip', 'Generic clip', 'Generic doc', 'Generic icon', 'Generic movie', 'Generic widescreen', 'Generic zip', 'Layers', 'Macro sheet', 'Mislabeled clip', 'Notes', 'Octet photo', 'Photo', 'Photoshop alias', 'Report', 'Scanned', 'Unknown']];
        yield 'document' => ['!is:document', ['Archive', 'Clip', 'Generic clip', 'Generic icon', 'Generic movie', 'Generic song', 'Generic widescreen', 'Generic zip', 'Layers', 'Mislabeled clip', 'Octet photo', 'Photo', 'Photoshop alias', 'Scanned', 'Song', 'Unknown']];
    }

    /**
     * @param string[] $expectedTitles
     */
    #[DataProvider('negatedMimeCommandProvider')]
    public function testNegatedMimeCommandReturnsAllNonMatchingAssets(string $searchString, array $expectedTitles): void
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
