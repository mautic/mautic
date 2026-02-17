<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\ProjectBundle\Tests\Functional\AbstractProjectSearchTestCase;

final class CampaignProjectSearchFunctionalTest extends AbstractProjectSearchTestCase
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

    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by two projects' => [
            'searchTerm'          => 'project:"Project One" OR project:"Project Three"',
            'expectedEntities'    => ['Campaign Alpha', 'Campaign Beta'],
            'unexpectedEntities'  => ['Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by project with AND operator' => [
            'searchTerm'          => 'project:"Project Two" AND campaign:"Alpha"',
            'expectedEntities'    => ['Campaign Alpha'],
            'unexpectedEntities'  => ['Campaign Beta', 'Campaign Gamma', 'Campaign Delta'],
        ];

        yield 'search by project with exclusion' => [
            'searchTerm'          => 'project:"Project Two" NOT campaign:"Beta"',
            'expectedEntities'    => ['Campaign Alpha'],
            'unexpectedEntities'  => ['Campaign Beta', 'Campaign Gamma', 'Campaign Delta'],
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
