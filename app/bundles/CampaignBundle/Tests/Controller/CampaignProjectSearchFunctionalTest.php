<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class CampaignProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $campaignAlpha = $this->createCampaign('Campaign Alpha');
        $campaignBeta  = $this->createCampaign('Campaign Beta');
        $this->createCampaign('Campaign Gamma');
        $this->createCampaign('Campaign Delta');

        $campaignAlpha->addProject($projectOne);
        $campaignAlpha->addProject($projectTwo);
        $campaignBeta->addProject($projectTwo);
        $campaignBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/campaigns', '/s/campaigns']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by one project AND campaign name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Alpha', 'Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by one project OR campaign name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Beta', 'Campaign Gamma'],
            'unexpectedEntities'  => ['Campaign Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Campaign Gamma', 'Campaign Delta'],
            'unexpectedEntities'  => ['Campaign Alpha', 'Campaign Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Alpha', 'Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Campaign Gamma', 'Campaign Delta'],
            'unexpectedEntities'  => ['Campaign Alpha', 'Campaign Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Gamma', 'Campaign Delta'],
            'unexpectedEntities'  => ['Campaign Beta'],
        ];
    }

    private function createCampaign(string $name): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($name);
        $this->em->persist($campaign);

        return $campaign;
    }
}
