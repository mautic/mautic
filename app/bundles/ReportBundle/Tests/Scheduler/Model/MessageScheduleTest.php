<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Model\MessageSchedule;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class MessageScheduleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testSendFile($fileSize, $limit)
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message')
            ->willReturn('Subject');

        $fileProperties = $this->getMockBuilder(FileProperties::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $router->expects($this->never())
            ->method('generate');

        $messageSchedule = new MessageSchedule($translatorMock, $fileProperties, $coreParametersHelper, $router);

        $report = new Report();

        $messageSchedule->getMessage($report, 'path-to-a-file');
    }

    public function testSendFileProvider()
    {
        return [
            [10, 100],
            [100, 100],
            [1, 1],
            [1, 1],
        ];
    }

    /**
     * @dataProvider testDoSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testDoSendFile($fileSize, $limit)
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message_file_not_attached')
            ->willReturn('Subject');

        $fileProperties = $this->getMockBuilder(FileProperties::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $router->expects($this->once())
            ->method('generate')
            ->with('mautic_report_view');

        $messageSchedule = new MessageSchedule($translatorMock, $fileProperties, $coreParametersHelper, $router);

        $report = new Report();

        $messageSchedule->getMessage($report, 'path-to-a-file');
    }

    public function testDoSendFileProvider()
    {
        return [
            [100, 10],
            [100, 99],
        ];
    }

    /**
     * @dataProvider testSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testFileCouldBeSend($fileSize, $limit)
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties = $this->getMockBuilder(FileProperties::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $messageSchedule = new MessageSchedule($translatorMock, $fileProperties, $coreParametersHelper, $router);

        $result = $messageSchedule->fileCouldBeSend('path-to-a-file');

        $this->assertTrue($result);
    }

    /**
     * @dataProvider testDoSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testFileCouldNotBeSend($fileSize, $limit)
    {
        $translatorMock = $this->getMockBuilder(TranslatorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties = $this->getMockBuilder(FileProperties::class)
            ->disableOriginalConstructor()
            ->getMock();

        $coreParametersHelper = $this->getMockBuilder(CoreParametersHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $router = $this->getMockBuilder(Router::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $coreParametersHelper->expects($this->once())
            ->method('getParameter')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $messageSchedule = new MessageSchedule($translatorMock, $fileProperties, $coreParametersHelper, $router);

        $result = $messageSchedule->fileCouldBeSend('path-to-a-file');

        $this->assertFalse($result);
    }
}
