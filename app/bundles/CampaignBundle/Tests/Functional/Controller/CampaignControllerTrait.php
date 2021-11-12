<?php

namespace Mautic\CampaignBundle\Tests\Functional\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

trait CampaignControllerTrait
{
    /**
     * @param array<string,mixed> $formValues
     */
    private function refreshAndSubmitForm(Campaign $campaign, int $expectedVersion, array $formValues = []): void
    {
        $crawler = $this->refreshPage($campaign);
        $this->submitForm($crawler, $campaign, $expectedVersion, $formValues);
    }

    private function refreshPage(Campaign $campaign): Crawler
    {
        $crawler = $this->client->request('GET', sprintf('/s/campaigns/edit/%s', $campaign->getId()));
        Assert::assertTrue($this->client->getResponse()->isOk());
        Assert::assertStringContainsString('Edit Campaign', $crawler->text());

        return $crawler;
    }

    /**
     * @param array<string,mixed> $formValues
     */
    private function submitForm(Crawler $crawler, Campaign $campaign, int $expectedVersion, array $formValues = []): Crawler
    {
        $form = $crawler->selectButton('Apply')->form();
        $form->setValues($formValues);
        $newCrawler = $this->client->submit($form);
        Assert::assertTrue($this->client->getResponse()->isOk());

        $this->em->clear();
        $campaign = $this->em->find(Campaign::class, $campaign->getId());
        Assert::assertSame($expectedVersion, $campaign->getVersion());

        return $newCrawler;
    }
}
