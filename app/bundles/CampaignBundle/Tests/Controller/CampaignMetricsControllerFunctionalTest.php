<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Mautic\CampaignBundle\Tests\Functional\Fixtures\FixtureHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Tests\Functional\Fixtures\EmailFixturesHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class CampaignMetricsControllerFunctionalTest extends MauticMysqlTestCase
{
    private FixtureHelper $campaignFixturesHelper;
    private EmailFixturesHelper $emailFixturesHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->campaignFixturesHelper = new FixtureHelper($this->em);
        $this->emailFixturesHelper    = new EmailFixturesHelper($this->em);
    }

    /**
     * @return array<string, mixed>
     */
    private function setupEmailCampaignTestData(): array
    {
        $contacts = [
            $this->campaignFixturesHelper->createContact('john@example.com'),
            $this->campaignFixturesHelper->createContact('paul@example.com'),
        ];

        $email = $this->emailFixturesHelper->createEmail('Test Email');
        $this->em->flush();

        $campaign      = $this->campaignFixturesHelper->createCampaignWithEmailSent($email->getId());
        $this->campaignFixturesHelper->addContactToCampaign($contacts[0], $campaign);
        $this->campaignFixturesHelper->addContactToCampaign($contacts[1], $campaign);
        $eventId = $campaign->getEmailSendEvents()->first()->getId();

        $emailStats = [
            $this->emailFixturesHelper->emulateEmailSend($contacts[0], $email, '2024-12-10 12:00:00', 'campaign.event', $eventId),
            $this->emailFixturesHelper->emulateEmailSend($contacts[1], $email, '2024-12-10 12:00:00', 'campaign.event', $eventId),
        ];

        $this->emailFixturesHelper->emulateEmailRead($emailStats[0], $email, '2024-12-10 12:09:00');
        $this->emailFixturesHelper->emulateEmailRead($emailStats[1], $email, '2024-12-11 21:35:00');

        $this->em->flush();
        $this->em->persist($email);

        $emailLinks = [
            $this->emailFixturesHelper->createEmailLink('https://example.com/1', $email->getId()),
            $this->emailFixturesHelper->createEmailLink('https://example.com/2', $email->getId()),
        ];
        $this->em->flush();

        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[0], $contacts[0], '2024-12-10 12:10:00', 3);
        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[1], $contacts[0], '2024-12-10 13:20:00');
        $this->emailFixturesHelper->emulateLinkClick($email, $emailLinks[1], $contacts[1], '2024-12-11 21:37:00');
        $this->em->flush();

        return ['campaign' => $campaign, 'email' => $email];
    }

    public function testEmailWeekdaysAction(): void
    {
        $testData = $this->setupEmailCampaignTestData();
        $campaign = $testData['campaign'];

        $this->client->request(Request::METHOD_GET, "/s/campaign/metrics/email-weekdays/{$campaign->getId()}/2024-12-01/2024-12-12");
        Assert::assertTrue($this->client->getResponse()->isOk());
        $content      = $this->client->getResponse()->getContent();
        $crawler      = new Crawler($content);
        $daysJson     = $crawler->filter('canvas')->text(null, false);
        $daysData     = json_decode(html_entity_decode($daysJson), true);
        $daysDatasets = $daysData['datasets'];
        Assert::assertIsArray($daysDatasets);
        Assert::assertCount(3, $daysDatasets);  // Assuming there are 3 datasets: Email sent, Email read, Email clicked

        $expectedDaysLabels = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $expectedDaysData   = [
            ['label' => 'Email sent', 'data' => [0, 2, 0, 0, 0, 0, 0]],
            ['label' => 'Email read', 'data' => [0, 1, 1, 0, 0, 0, 0]],
            ['label' => 'Email clicked', 'data' => [0, 4, 1, 0, 0, 0, 0]],
        ];
        Assert::assertEquals($expectedDaysLabels, $daysData['labels']);
        foreach ($daysDatasets as $index => $dataset) {
            Assert::assertEquals($expectedDaysData[$index]['label'], $dataset['label']);
            Assert::assertEquals($expectedDaysData[$index]['data'], $dataset['data']);
        }
    }

    public function testEmailHoursAction(): void
    {
        $testData = $this->setupEmailCampaignTestData();
        $campaign = $testData['campaign'];

        $this->client->request(Request::METHOD_GET, "/s/campaign/metrics/email-hours/{$campaign->getId()}/2024-12-01/2024-12-12");
        Assert::assertTrue($this->client->getResponse()->isOk());
        $content   = $this->client->getResponse()->getContent();
        $crawler   = new Crawler($content);
        $hourJson  = $crawler->filter('canvas')->text(null, false);
        $hoursData = json_decode(html_entity_decode($hourJson), true);

        $hoursDatasets = $hoursData['datasets'];
        Assert::assertIsArray($hoursDatasets);
        Assert::assertCount(3, $hoursDatasets);  // Assuming there are 3 datasets: Email sent, Email read, Email clicked

        // Get the time format from CoreParametersHelper
        $coreParametersHelper = self::getContainer()->get('mautic.helper.core_parameters');
        $timeFormat           = $coreParametersHelper->get('date_format_timeonly');

        // Generate expected hour labels based on the actual time format
        $expectedHoursLabels = [];
        for ($hour = 0; $hour < 24; ++$hour) {
            $startTime             = (new \DateTime())->setTime($hour, 0);
            $endTime               = (new \DateTime())->setTime(($hour + 1) % 24, 0);
            $expectedHoursLabels[] = $startTime->format($timeFormat).' - '.$endTime->format($timeFormat);
        }

        Assert::assertEquals($expectedHoursLabels, $hoursData['labels']);

        $expectedHoursData = [
            ['label' => 'Email sent', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]],
            ['label' => 'Email read', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0]],
            ['label' => 'Email clicked', 'data' => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 3, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0]],
        ];
        foreach ($hoursDatasets as $index => $dataset) {
            Assert::assertEquals($expectedHoursData[$index]['label'], $dataset['label']);
            Assert::assertEquals($expectedHoursData[$index]['data'], $dataset['data']);
        }
    }
}
