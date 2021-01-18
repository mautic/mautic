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

    protected function setUp()
    {
        parent::setUp();
        $this->prefix = $this->container->getParameter('mautic.db_table_prefix');
        $this->reportModel = $this->container->get('mautic.report.model.report');
    }

    public function testThatGetReportDataUsesCorrectDataRange(): void
    {

        $reportData = [
            'is_published' => 1,
            'name' => 'Test Report',
            'system' => 0,
            'source' => 'form.submissions',
            'is_scheduled' => 0,
        ];
        $this->connection->insert($this->prefix . 'reports', $reportData);
        $reportId = $this->connection->lastInsertId();

        $formData = [
            'is_published' => 1,
            'name' => 'Test Form',
            'alias' => 'create_a_c',
            'post_action' => 'return',
        ];

        $this->connection->insert($this->prefix . 'forms', $formData);
        $formId = $this->connection->lastInsertId();



        $this->reportModel->getReportData();
    }
}
