<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Unit\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Model\ReportModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\Form\FormFactory;

final class ReportModelTest extends MauticMysqlTestCase
{
    /**
     * @var ReportModel
     */
    private $reportModel;

    /**
     * @var string
     */
    protected $prefix = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix      = $this->container->getParameter('mautic.db_table_prefix');
        $this->reportModel = $this->container->get('mautic.report.model.report');
    }

    public function testThatGetReportDataUsesCorrectDataRange(): void
    {
        $columns = [
            'fs.date_submitted',
        ];

        $reportData = [
            'is_published' => 1,
            'name'         => 'Test Report',
            'system'       => 0,
            'source'       => 'form.submissions',
            'is_scheduled' => 0,
            'columns'      => serialize($columns),
            'filters'      => serialize([]),
            'table_order'  => serialize([]),
            'graphs'       => serialize([]),
            'group_by'     => serialize([]),
            'aggregators'  => serialize([]),
            'settings'     => json_encode([
                'showDynamicFilters'   => 0,
                'hideDateRangeFilter'  => 0,
                'showGraphsAboveTable' => 0,
            ]),
        ];

        $this->connection->insert($this->prefix.'reports', $reportData);
        $reportId = $this->connection->lastInsertId();

        $formData = [
            'is_published' => 1,
            'name'         => 'Test Form',
            'alias'        => 'create_a_c',
            'post_action'  => 'return',
        ];

        $this->connection->insert($this->prefix.'forms', $formData);
        $formId = $this->connection->lastInsertId();

        $ipData = [
            'ip_address' => '127.0.0.1',
            'ip_details' => 'N;',
        ];

        $this->connection->insert($this->prefix.'ip_addresses', $ipData);
        $ipAddressId = $this->connection->lastInsertId();

        $utc = new \DateTimeZone('UTC');
        // I know I can use \DateTimeImmutable, but getReportData expects \DateTime
        $now        = new \DateTime('now', $utc);
        $aDayAgo    = (clone $now)->modify('-1 day');
        $twoDaysAgo = (clone $now)->modify('-2 days');
        $format     = 'Y-m-d H:i:s';

        $formSubmissionsData = [
            [
                'form_id'        => $formId,
                'ip_id'          => $ipAddressId,
                'date_submitted' => $twoDaysAgo->format($format),
                'referer'        => 'https://mautic-cloud.local/index_dev.php/test',
            ],
            [
                'form_id'        => $formId,
                'ip_id'          => $ipAddressId,
                'date_submitted' => $aDayAgo->format($format),
                'referer'        => 'https://mautic-cloud.local/index_dev.php/test',
            ],
            [
                'form_id'        => $formId,
                'ip_id'          => $ipAddressId,
                'date_submitted' => $now->format($format),
                'referer'        => 'https://mautic-cloud.local/index_dev.php/test',
            ],
        ];

        foreach ($formSubmissionsData as $formSubmissionData) {
            $this->connection->insert($this->prefix.'form_submissions', $formSubmissionData);
        }

        /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');

        /** @var ReportModel $reportModel */
        $reportModel = $this->container->get('mautic.model.factory')->getModel('report');

        $report = $reportModel->getEntity($reportId);

        $aDayAgoBeginningOfTheDay = (clone $aDayAgo)->setTime(0, 0, 0);

        $reportData = $this->reportModel->getReportData($report, $formFactory, [
            'dateFrom' => $aDayAgoBeginningOfTheDay,
            'dateTo'   => clone $aDayAgoBeginningOfTheDay,
        ]);

        Assert::assertSame(1, $reportData['totalResults']);
        Assert::assertCount(1, $reportData['data']);
    }
}
