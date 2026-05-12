<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Functional\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\ReportBundle\Entity\Report;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

final class ReportApiDateHandlingTest extends MauticMysqlTestCase
{
    private Report $report;
    private Form $form;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test entities
        $this->report = new Report();
        $this->report->setName('Date Handling Test Report');
        $this->report->setSource('form.submissions');
        $this->report->setColumns(['fs.date_submitted', 'fs.id']);
        $this->report->setSettings([]);

        $this->form = new Form();
        $this->form->setName('Test Form for Date Handling');
        $this->form->setAlias('date_test_form');

        $this->em->persist($this->report);
        $this->em->persist($this->form);
        $this->em->flush();
    }

    public function testApiReportWithDateFromAndDateToParameters(): void
    {
        // Arrange: Create submissions at specific times
        $yesterday   = new \DateTime('2025-08-16 06:30:00', new \DateTimeZone('UTC')); // Yesterday
        $targetTime1 = new \DateTime('2025-08-15 06:30:00', new \DateTimeZone('UTC')); // Within range
        $targetTime2 = new \DateTime('2025-08-15 07:00:00', new \DateTimeZone('UTC')); // Within range
        $beforeTime  = new \DateTime('2025-08-15 06:00:00', new \DateTimeZone('UTC'));  // Before range
        $afterTime   = new \DateTime('2025-08-15 08:00:00', new \DateTimeZone('UTC'));   // After range
        $tomorrow    = new \DateTime('2025-08-16 08:00:00', new \DateTimeZone('UTC'));   // After range

        $submission0 = $this->createSubmission($yesterday);
        $submission1 = $this->createSubmission($targetTime1);
        $submission2 = $this->createSubmission($targetTime2);
        $submission3 = $this->createSubmission($beforeTime);
        $submission4 = $this->createSubmission($afterTime);
        $submission5 = $this->createSubmission($tomorrow);

        $this->em->persist($submission0);
        $this->em->persist($submission1);
        $this->em->persist($submission2);
        $this->em->persist($submission3);
        $this->em->persist($submission4);
        $this->em->persist($submission5);
        $this->em->flush();

        // Act: Make API request with dateFrom and dateTo parameters
        $this->client->request(
            Request::METHOD_GET,
            "/api/reports/{$this->report->getId()}?dateFrom=2025-08-15T06:14:10&dateTo=2025-08-15T07:13:23"
        );

        // Assert: Verify response
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);

        // Verify report structure
        Assert::assertArrayHasKey('report', $responseData);
        Assert::assertArrayHasKey('data', $responseData);
        Assert::assertArrayHasKey('totalResults', $responseData);
        Assert::assertArrayHasKey('dateFrom', $responseData);
        Assert::assertArrayHasKey('dateTo', $responseData);

        // The key assertion: verify that only submissions within the time range are included
        // Given the dateFrom=2025-08-15T06:14:10 and dateTo=2025-08-15T07:13:23
        // Only submission1 (06:30:00) and submission2 (07:00:00) should be included
        Assert::assertEquals(2, $responseData['totalResults'], 'Should return exactly 2 submissions within the date range');
        Assert::assertCount(2, $responseData['data'], 'Data array should contain exactly 2 records');

        // Now test that UI still show records for the whole day.
        $crawler = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$this->report->getId()}");

        $this->assertResponseIsSuccessful();

        $buttonCrawler = $crawler->selectButton('Save');
        $form          = $buttonCrawler->form();

        $form->setValues([
            'daterange[date_from]' => '2025-08-15',
            'daterange[date_to]'   => '2025-08-15',
        ]);

        $crawler = $this->client->submit($form);
        $this->assertResponseIsSuccessful();
        // last row is for totals. So 4+1
        $this->assertCount(5, $crawler->filter('#reportTable tbody tr'), $crawler->html());
    }

    public function testApiReportWithTimezoneConversion(): void
    {
        // Arrange: Create submission at specific UTC time
        $utcTime    = new \DateTime('2025-08-15 14:30:00', new \DateTimeZone('UTC'));
        $submission = $this->createSubmission($utcTime);
        $this->em->persist($submission);
        $this->em->flush();

        // Act: Make API request with timezone-aware parameters
        // Using New York timezone (UTC-4 in August): 10:00 NY = 14:00 UTC, 16:00 NY = 20:00 UTC
        $this->client->request(
            Request::METHOD_GET,
            "/api/reports/{$this->report->getId()}?dateFrom=2025-08-15T10:00:00-04:00&dateTo=2025-08-15T16:00:00-04:00"
        );

        // Assert: Verify response includes the submission
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);

        // The submission at 14:30 UTC should be included in the range 10:00-16:00 NY time (14:00-20:00 UTC)
        Assert::assertEquals(1, $responseData['totalResults'], 'Should include submission within timezone-converted range');
    }

    public function testApiReportWithoutTime(): void
    {
        $this->em->persist($this->createSubmission(new \DateTime('2025-08-14 23:59:59', new \DateTimeZone('UTC'))));
        $this->em->persist($this->createSubmission(new \DateTime('2025-08-15 14:30:00', new \DateTimeZone('UTC'))));
        $this->em->persist($this->createSubmission(new \DateTime('2025-08-16 00:00:00', new \DateTimeZone('UTC'))));
        $this->em->flush();

        $this->client->request(
            Request::METHOD_GET,
            "/api/reports/{$this->report->getId()}?dateFrom=2025-08-15&dateTo=2025-08-15"
        );

        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response->getContent(), true);

        // 1 submission should be included in the results as we consider dateFrom time as 00:00:00 and dateTo time as 23:59:59 UTC if not set.
        Assert::assertEquals(1, $responseData['totalResults'], 'Should include submission within timezone-converted range');
    }

    private function createSubmission(\DateTime $dateSubmitted): Submission
    {
        $submission = new Submission();
        $submission->setForm($this->form);
        $submission->setDateSubmitted($dateSubmitted);
        $submission->setReferer('');

        return $submission;
    }
}
