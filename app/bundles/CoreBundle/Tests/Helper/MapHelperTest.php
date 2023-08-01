<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Helper;

use Mautic\CampaignBundle\Controller\CampaignMapStatsController;
use Mautic\CoreBundle\Controller\AbstractCountryMapController;
use Mautic\CoreBundle\Helper\MapHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class MapHelperTest extends MauticMysqlTestCase
{
    /**
     * @return array<int, array<string, string>>
     */
    private function getStats(): array
    {
        return [
            [
                'sent_count'            => '4',
                'read_count'            => '4',
                'clicked_through_count' => '4',
                'country'               => '',
            ],
            [
                'sent_count'            => '12',
                'read_count'            => '8',
                'clicked_through_count' => '4',
                'country'               => 'Spain',
            ],
            [
                'sent_count'            => '8',
                'read_count'            => '8',
                'clicked_through_count' => '4',
                'country'               => 'Finland',
            ],
        ];
    }

    public function testGetOptionLegendText(): void
    {
        $legendValues = [
            '%total'       => 4,
            '%withCountry' => 2,
        ];

        $this->assertEquals(
            'Total: 4 (2 with country)',
            MapHelper::getOptionLegendText(AbstractCountryMapController::LEGEND_TEXT, $legendValues)
        );
    }

    public function testBuildMapData(): void
    {
        $results = MapHelper::buildMapData(
            $this->getStats(),
            CampaignMapStatsController::MAP_OPTIONS,
            AbstractCountryMapController::LEGEND_TEXT
        );

        $this->assertCount(2, $results);
        $this->assertSame([
            'data' => [
                'ES' => 8,
                'FI' => 8,
            ],
            'label'      => 'mautic.email.stat.read',
            'legendText' => 'Total: 20 (16 with country)',
            'unit'       => 'Read',
        ], $results[0]);

        $this->assertSame([
            'data' => [
                'ES' => 4,
                'FI' => 4,
            ],
            'label'      => 'mautic.email.clicked',
            'legendText' => 'Total: 12 (8 with country)',
            'unit'       => 'Click',
        ], $results[1]);
    }
}
