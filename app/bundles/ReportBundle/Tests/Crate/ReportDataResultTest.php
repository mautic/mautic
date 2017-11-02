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
use Mautic\ReportBundle\Tests\Fixures;

class ReportDataResultTest extends \PHPUnit_Framework_TestCase
{
    public function testValidData()
    {
        $reportDataResult = new ReportDataResult(Fixures::getValidReportResult());

        $this->assertSame(Fixures::getValidReportData(), $reportDataResult->getData());
        $this->assertSame(Fixures::getValidReportHeaders(), $reportDataResult->getHeaders());
        $this->assertSame(Fixures::getValidReportTotalResult(), $reportDataResult->getTotalResults());
        $this->assertSame(Fixures::getStringType(), $reportDataResult->getType('city'));
        $this->assertSame(Fixures::getDateType(), $reportDataResult->getType('date_identified'));
        $this->assertSame(Fixures::getEmailType(), $reportDataResult->getType('email'));
    }

    public function testNoDataProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixures::getValidReportResult();
        unset($data['data']);
        new ReportDataResult($data);
    }

    public function testNoDataColumnProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixures::getValidReportResult();
        unset($data['dataColumns']);
        new ReportDataResult($data);
    }

    public function testNoColumnProvided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixures::getValidReportResult();
        unset($data['columns']);
        new ReportDataResult($data);
    }
}
