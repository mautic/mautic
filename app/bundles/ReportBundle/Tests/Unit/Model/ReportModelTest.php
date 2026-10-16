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
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @param list<string> $submittedAt
     */
    #[DataProvider('totalCountQueryProvider')]
    public function testGetTotalCountBuildsCompatibleCountQuery(
        string $alias,
        string $ipAddress,
        array $submittedAt,
        bool $useOnlyFullGroupBy,
        bool $grouped,
        ?string $having,
        int $expectedTotal,
    ): void {
        $form = $this->createFormWithSubmissions('Report Count Test Form', $alias, $ipAddress, $submittedAt);
        $test = function () use ($form, $grouped, $having, $expectedTotal): void {
            /** @var ReportModel $reportModel */
            $reportModel = self::getContainer()->get(ReportModel::class);
            $debugData   = [];
            $query       = $this->createReportQuery($form, $grouped, $having);

            $this->assertSame($expectedTotal, $this->invokeGetTotalCount($reportModel, $query, $debugData));
        };

        if ($useOnlyFullGroupBy) {
            $this->withOnlyFullGroupBy($test);

            return;
        }

        $test();
    }

    /**
     * @return iterable<string, array{
     *     alias: string,
     *     ipAddress: string,
     *     submittedAt: list<string>,
     *     useOnlyFullGroupBy: bool,
     *     grouped: bool,
     *     having: string|null,
     *     expectedTotal: int
     * }>
     */
    public static function totalCountQueryProvider(): iterable
    {
        yield 'grouped report with strict group-by mode' => [
            'alias'              => 'grouped_report_test_form',
            'ipAddress'          => '127.0.0.2',
            'submittedAt'        => ['2026-01-01 10:00:00', '2026-01-01 11:00:00', '2026-01-02 10:00:00'],
            'useOnlyFullGroupBy' => true,
            'grouped'            => true,
            'having'             => null,
            'expectedTotal'      => 2,
        ];

        yield 'non-grouped report' => [
            'alias'              => 'non_grouped_report_test_form',
            'ipAddress'          => '127.0.0.3',
            'submittedAt'        => ['2026-02-01 10:00:00', '2026-02-01 11:00:00', '2026-02-02 10:00:00'],
            'useOnlyFullGroupBy' => false,
            'grouped'            => false,
            'having'             => null,
            'expectedTotal'      => 3,
        ];

        yield 'grouped report with having and strict group-by mode' => [
            'alias'              => 'grouped_report_having_test_form',
            'ipAddress'          => '127.0.0.4',
            'submittedAt'        => ['2026-03-01 10:00:00', '2026-03-01 11:00:00', '2026-03-02 10:00:00'],
            'useOnlyFullGroupBy' => true,
            'grouped'            => true,
            'having'             => 'COUNT(fs.id) > 1',
            'expectedTotal'      => 1,
        ];
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

    /**
     * @param list<string> $submittedAt
     */
    private function createFormWithSubmissions(string $name, string $alias, string $ipAddress, array $submittedAt): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias($alias);

        $ip = new IpAddress($ipAddress);

        $this->em->persist($ip);
        $this->em->persist($form);
        $this->em->flush();

        $timezone = new \DateTimeZone('UTC');
        foreach ($submittedAt as $dateSubmitted) {
            $this->em->persist($this->makeSubmission($form, $ip, new \DateTime($dateSubmitted, $timezone)));
        }

        $this->em->flush();

        return $form;
    }

    private function withOnlyFullGroupBy(callable $callback): void
    {
        $connection      = $this->em->getConnection();
        $originalSqlMode = (string) $connection->executeQuery('SELECT @@SESSION.sql_mode')->fetchOne();

        try {
            $sqlModes = array_filter(explode(',', $originalSqlMode));
            if (!in_array('ONLY_FULL_GROUP_BY', $sqlModes, true)) {
                $sqlModes[] = 'ONLY_FULL_GROUP_BY';
                $connection->executeStatement('SET SESSION sql_mode = ?', [implode(',', $sqlModes)]);
            }

            $callback();
        } finally {
            $connection->executeStatement('SET SESSION sql_mode = ?', [$originalSqlMode]);
        }
    }

    private function createReportQuery(Form $form, bool $grouped = false, ?string $having = null): QueryBuilder
    {
        $queryBuilder = $this->em->getConnection()->createQueryBuilder();
        $queryBuilder->select('fs.id', 'fs.date_submitted')
            ->from(MAUTIC_TABLE_PREFIX.'form_submissions', 'fs')
            ->where('fs.form_id = :formId')
            ->setParameter('formId', $form->getId())
            ->orderBy('fs.id', 'DESC')
            ->setMaxResults(1);

        if ($grouped) {
            $queryBuilder->groupBy('DATE(fs.date_submitted)');
        }

        if (null !== $having) {
            $queryBuilder->having($having);
        }

        return $queryBuilder;
    }

    private function invokeGetTotalCount(ReportModel $reportModel, QueryBuilder $queryBuilder, array &$debugData): int
    {
        $method = new \ReflectionMethod($reportModel, 'getTotalCount');
        $result = $method->invokeArgs($reportModel, [$queryBuilder, &$debugData]);
        \assert(is_int($result));

        return $result;
    }
}
