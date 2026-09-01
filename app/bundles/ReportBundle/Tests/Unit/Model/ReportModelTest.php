<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Unit\Model;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\Submission;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

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

        $session = $this->createStub(Session::class);
        $request = new Request();
        $request->setSession($session);
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($request);
        /** @var ReportModel $reportModel */
        $reportModel = self::getContainer()->get(ReportModel::class);

        $aDayAgoBeginningOfTheDay = (clone $aDayAgo)->setTime(0, 0, 0);

        $reportData = $reportModel->getReportData($report, null, [
            'dateFrom' => $aDayAgoBeginningOfTheDay,
            'dateTo'   => clone $aDayAgoBeginningOfTheDay,
        ]);

        $this->assertSame(1, $reportData['totalResults']);
        $this->assertCount(1, $reportData['data']);
    }

    public function testGetTotalCountUsesStrictModeCompatibleQueryForGroupedReports(): void
    {
        $form = new Form();
        $form->setName('Grouped Report Test Form');
        $form->setAlias('grouped_report_test_form');

        $ip = new IpAddress('127.0.0.2');

        $this->em->persist($ip);
        $this->em->persist($form);
        $this->em->flush();

        $timezone = new \DateTimeZone('UTC');
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-01-01 10:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-01-01 11:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-01-02 10:00:00', $timezone)));
        $this->em->flush();

        $connection      = $this->em->getConnection();
        $originalSqlMode = (string) $connection->executeQuery('SELECT @@SESSION.sql_mode')->fetchOne();

        try {
            $sqlModes = array_filter(explode(',', $originalSqlMode));
            if (!in_array('ONLY_FULL_GROUP_BY', $sqlModes, true)) {
                $sqlModes[] = 'ONLY_FULL_GROUP_BY';
                $connection->executeStatement('SET SESSION sql_mode = ?', [implode(',', $sqlModes)]);
            }

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->select('fs.id', 'fs.date_submitted')
                ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
                ->where('fs.form_id = :formId')
                ->setParameter('formId', $form->getId())
                ->groupBy('DATE(fs.date_submitted)')
                ->orderBy('fs.id', 'DESC')
                ->setMaxResults(1);

            /** @var ReportModel $reportModel */
            $reportModel = self::getContainer()->get(ReportModel::class);
            $debugData   = [];

            $this->assertSame(2, $this->invokeGetTotalCount($reportModel, $queryBuilder, $debugData));
        } finally {
            $connection->executeStatement('SET SESSION sql_mode = ?', [$originalSqlMode]);
        }
    }

    public function testGetTotalCountSupportsNonGroupedReports(): void
    {
        $form = new Form();
        $form->setName('Non Grouped Report Test Form');
        $form->setAlias('non_grouped_report_test_form');

        $ip = new IpAddress('127.0.0.3');

        $this->em->persist($ip);
        $this->em->persist($form);
        $this->em->flush();

        $timezone = new \DateTimeZone('UTC');
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-02-01 10:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-02-01 11:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-02-02 10:00:00', $timezone)));
        $this->em->flush();

        $queryBuilder = $this->em->getConnection()->createQueryBuilder();
        $queryBuilder->select('fs.id', 'fs.date_submitted')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
            ->where('fs.form_id = :formId')
            ->setParameter('formId', $form->getId())
            ->orderBy('fs.id', 'DESC')
            ->setMaxResults(1);

        /** @var ReportModel $reportModel */
        $reportModel = self::getContainer()->get(ReportModel::class);
        $debugData   = [];

        $this->assertSame(3, $this->invokeGetTotalCount($reportModel, $queryBuilder, $debugData));
    }

    public function testGetTotalCountPreservesHavingForGroupedReports(): void
    {
        $form = new Form();
        $form->setName('Grouped Report Having Test Form');
        $form->setAlias('grouped_report_having_test_form');

        $ip = new IpAddress('127.0.0.4');

        $this->em->persist($ip);
        $this->em->persist($form);
        $this->em->flush();

        $timezone = new \DateTimeZone('UTC');
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-03-01 10:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-03-01 11:00:00', $timezone)));
        $this->em->persist($this->makeSubmission($form, $ip, new \DateTime('2026-03-02 10:00:00', $timezone)));
        $this->em->flush();

        $connection      = $this->em->getConnection();
        $originalSqlMode = (string) $connection->executeQuery('SELECT @@SESSION.sql_mode')->fetchOne();

        try {
            $sqlModes = array_filter(explode(',', $originalSqlMode));
            if (!in_array('ONLY_FULL_GROUP_BY', $sqlModes, true)) {
                $sqlModes[] = 'ONLY_FULL_GROUP_BY';
                $connection->executeStatement('SET SESSION sql_mode = ?', [implode(',', $sqlModes)]);
            }

            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->select('fs.id', 'fs.date_submitted')
                ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
                ->where('fs.form_id = :formId')
                ->setParameter('formId', $form->getId())
                ->groupBy('DATE(fs.date_submitted)')
                ->having('COUNT(fs.id) > 1')
                ->orderBy('fs.id', 'DESC')
                ->setMaxResults(1);

            /** @var ReportModel $reportModel */
            $reportModel = self::getContainer()->get(ReportModel::class);
            $debugData   = [];

            $this->assertSame(1, $this->invokeGetTotalCount($reportModel, $queryBuilder, $debugData));
        } finally {
            $connection->executeStatement('SET SESSION sql_mode = ?', [$originalSqlMode]);
        }
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

    private function invokeGetTotalCount(ReportModel $reportModel, QueryBuilder $queryBuilder, array &$debugData): int
    {
        $method = new \ReflectionMethod($reportModel, 'getTotalCount');
        $result = $method->invokeArgs($reportModel, [$queryBuilder, &$debugData]);
        \assert(is_int($result));

        return $result;
    }
}
