<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Command\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class CampaignApiControllerFunctionalTest extends MauticMysqlTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('withContactCountsProvider')]
    public function testCampaignAPI(string $withContactCounts, bool $fromCache, int $expectedContacts): void
    {
        $contact  = $this->createLead('Test');
        $campaign = $this->createCampaign('My campaign');
        $this->createCampaignLead($campaign, $contact);
        $this->em->flush();
        $this->em->clear();

        $cacheProvider = self::getContainer()->get('mautic.cache.provider');
        if ($fromCache) {
            $contactCountDetail = [
                'contactCount'   => $expectedContacts,
                'countFetchedAt' => (new DateTimeHelper())->toUtcString(),
            ];
            $item = $cacheProvider->getItem(sprintf('%s.%s.%s', 'campaign', $campaign->getId(), 'lead'));
            $item->set($contactCountDetail);
            $cacheProvider->save($item);
        }
        $this->client->request(Request::METHOD_GET, '/api/campaigns?withContactCounts='.$withContactCounts);
        $clientResponse = $this->client->getResponse();
        $this->assertTrue($clientResponse->isOk());
        $response = json_decode($clientResponse->getContent(), true);
        Assert::assertArrayHasKey('campaigns', $response);
        Assert::assertArrayHasKey($campaign->getId(), $response['campaigns']);
        if ('true' === $withContactCounts) {
            Assert::assertArrayHasKey(
                'contactCount',
                $response['campaigns'][$campaign->getId()],
            );
            Assert::assertArrayHasKey(
                'contactCountFetchedAt',
                $response['campaigns'][$campaign->getId()],
            );
            Assert::assertSame($expectedContacts, $response['campaigns'][$campaign->getId()]['contactCount']);
        } else {
            Assert::assertArrayNotHasKey(
                'contactCount',
                $response['campaigns'][$campaign->getId()],
                'contactCount should not be present without withContactCounts parameter'
            );
            Assert::assertArrayNotHasKey(
                'contactCountFetchedAt',
                $response['campaigns'][$campaign->getId()],
            );
        }
        if ($fromCache) {
            $cacheProvider->deleteItem(sprintf('%s.%s.%s', 'campaign', $campaign->getId(), 'lead'));
        }
    }

    /**
     * @return iterable<mixed>
     */
    public static function withContactCountsProvider(): iterable
    {
        yield ['true', false, 1];
        yield ['true', true, 2];
        yield ['false', false, 0];
    }

    private function createLead(string $leadName): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($leadName);
        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaign(string $campaignName): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($campaignName);
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createCampaignLead(Campaign $campaign, Lead $lead, bool $manuallyRemoved = false, int $rotation = 1): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $campaignLead->setManuallyRemoved($manuallyRemoved);
        $campaignLead->setRotation($rotation);
        $this->em->persist($campaignLead);

        return $campaignLead;
    }
}
