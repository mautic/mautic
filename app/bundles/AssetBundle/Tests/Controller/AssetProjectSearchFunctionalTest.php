<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Controller;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class AssetProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $assetAlpha = $this->createAsset('Asset Alpha');
        $assetBeta  = $this->createAsset('Asset Beta');
        $this->createAsset('Asset Gamma');
        $this->createAsset('Asset Delta');

        $assetAlpha->addProject($projectOne);
        $assetAlpha->addProject($projectTwo);
        $assetBeta->addProject($projectTwo);
        $assetBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/assets', '/s/assets']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Asset Alpha', 'Asset Beta'],
            'unexpectedEntities'  => ['Asset Gamma', 'Asset Delta'],
        ];

        yield 'search by one project AND asset name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Asset Beta'],
            'unexpectedEntities'  => ['Asset Alpha', 'Asset Gamma', 'Asset Delta'],
        ];

        yield 'search by one project OR asset name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Asset Alpha', 'Asset Beta', 'Asset Gamma'],
            'unexpectedEntities'  => ['Asset Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Asset Gamma', 'Asset Delta'],
            'unexpectedEntities'  => ['Asset Alpha', 'Asset Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Asset Beta'],
            'unexpectedEntities'  => ['Asset Alpha', 'Asset Gamma', 'Asset Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Asset Gamma', 'Asset Delta'],
            'unexpectedEntities'  => ['Asset Alpha', 'Asset Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Asset Alpha', 'Asset Beta'],
            'unexpectedEntities'  => ['Asset Gamma', 'Asset Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Asset Alpha', 'Asset Gamma', 'Asset Delta'],
            'unexpectedEntities'  => ['Asset Beta'],
        ];
    }

    private function createAsset(string $name): Asset
    {
        $asset = new Asset();
        $asset->setTitle($name);
        $asset->setAlias($name);
        $this->em->persist($asset);

        return $asset;
    }
}
