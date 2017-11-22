<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Crate;

use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Tests\Fixtures;

class ReportDataResultTest extends \PHPUnit_Framework_TestCase
{
    public function testValidData()
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $this->assertSame(Fixtures::getValidReportData(), $reportDataResult->getData());
        $this->assertSame(Fixtures::getValidReportHeaders(), $reportDataResult->getHeaders());
        $this->assertSame(Fixtures::getValidReportTotalResult(), $reportDataResult->getTotalResults());
        $this->assertSame(Fixtures::getStringType(), $reportDataResult->getType('city'));
        $this->assertSame(Fixtures::getDateType(), $reportDataResult->getType('date_identified'));
        $this->assertSame(Fixtures::getEmailType(), $reportDataResult->getType('email'));
    }

    public function testNoDataProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['data']);
        new ReportDataResult($data);
    }

    public function testNoDataColumnProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['dataColumns']);
        new ReportDataResult($data);
    }

    public function testNoColumnProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['columns']);
        new ReportDataResult($data);
    }
}
