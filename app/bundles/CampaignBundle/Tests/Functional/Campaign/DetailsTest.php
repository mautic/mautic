<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;

class DetailsTest extends MauticMysqlTestCase
{
    public function testDetailsPageLoadCorrectly(): void
    {
        $description = '<p><b>line1</b></p><p><em>line2</em></p><p><u>line3</u></p>';
        $campaign    = new Campaign();
        $campaign->setName('Campaign A');
        $campaign->setDescription($description);
        $campaign->setCanvasSettings([
            'nodes' => [
                0 => [
                    'id'        => '148',
                    'positionX' => '760',
                    'positionY' => '155',
                ],
                1 => [
                    'id'        => 'lists',
                    'positionX' => '860',
                    'positionY' => '50',
                ],
            ],
            'connections' => [
                0 => [
                    'sourceId' => 'lists',
                    'targetId' => '148',
                    'anchors'  => [
                        'source' => 'leadsource',
                        'target' => 'top',
                    ],
                ],
            ],
        ]
        );
        $this->em->persist($campaign);
        $this->em->flush();

        $crawler = $this->client->request('GET', sprintf('/s/campaigns/view/%s', $campaign->getId()));

        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertStringContainsString($campaign->getName(), $response->getContent());
        Assert::assertStringContainsString(sprintf('data-target-url="/s/campaigns/view/%s/contact/1"', $campaign->getId()), $response->getContent());
        Assert::assertSame($description, $crawler->filter('#campaign_description')->html());
    }
}
