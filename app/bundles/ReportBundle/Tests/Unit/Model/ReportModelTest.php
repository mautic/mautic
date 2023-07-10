<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Unit\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;
use PHPUnit\Framework\Assert;

final class ReportModelTest extends MauticMysqlTestCase
{
    public function testThatGetReportDataUsesCorrectDataRange(): void
    {
        $report = new Report();
        $report->setName('Test Report');
        $report->setSource('form.submissions');
        $report->setColumns(['fs.date_submitted']);
        $report->setSettings([]);

        $form = new Form();
        $form->setName('Test Form');
        $form->setAlias('create_a_c');

        $ip = new IpAddress('127.0.0.1');

        $this->em->persist($ip);
        $this->em->persist($report);
        $this->em->persist($form);
        $this->em->flush();

        // I know I can use \DateTimeImmutable, but getReportData expects \DateTime
        $now        = new \DateTime('now', new \DateTimeZone('UTC'));
        $aDayAgo    = (clone $now)->modify('-1 day');
        $twoDaysAgo = (clone $now)->modify('-2 days');

        $this->em->persist($this->makeSubmission($form, $ip, $twoDaysAgo));
        $this->em->persist($this->makeSubmission($form, $ip, $aDayAgo));
        $this->em->persist($this->makeSubmission($form, $ip, $now));

        $this->em->flush();

        $reportModel = self::$container->get('mautic.report.model.report');
        \assert($reportModel instanceof ReportModel);

        $aDayAgoBeginningOfTheDay = (clone $aDayAgo)->setTime(0, 0, 0);

        $reportData = $reportModel->getReportData($report, null, [
            'dateFrom' => $aDayAgoBeginningOfTheDay,
            'dateTo'   => clone $aDayAgoBeginningOfTheDay,
        ]);

        Assert::assertSame(1, $reportData['totalResults']);
        Assert::assertCount(1, $reportData['data']);
    }

    private function makeSubmission(Form $form, IpAddress $ipAddress, \DateTime $dateSubmitted): Submission
    {
        $submission = new Submission();
        $submission->setForm($form);
        $submission->setIpAddress($ipAddress);
        $submission->setDateSubmitted($dateSubmitted);
        $submission->setReferer('');

        return $submission;
    }
}
