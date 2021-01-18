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

        $ipData = [
            'ip_address' => '127.0.0.1',
            'N;',
        ];

        $this->connection->insert($this->prefix . 'ip_address', $ipData);
        $ipId = $this->connection->lastInsertId();

        $formSubmissionsData = [
            [
                'form_id' => $formId,
                'ip_id' => $ipId,
                'date_submitted' => '2021-01-14 22:20:34'
            ],
            [
                'form_id' => $formId,
                'ip_id' => $ipId,
                'date_submitted' => '2021-01-15 22:20:34'
            ],
            [
                'form_id' => $formId,
                'ip_id' => $ipId,
                'date_submitted' => '2021-01-16 22:20:34'
            ],
        ];

        foreach ($formSubmissionsData as $formSubmissionData) {
            $this->connection->insert($this->prefix . 'form_submissions'. $formSubmissionData);
        }

        $formFactory = $this->container->get('form.factory');
        /** @var ReportModel $reportModel */
        $reportModel = $this->container->get('mautic.model.factory')->getModel('report');
        $report = $reportModel->getEntity($reportId);

        $options = [];

        $this->reportModel->getReportData($report, $formFactory, []);
    }
}
