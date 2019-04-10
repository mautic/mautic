<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Scheduler\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\FileProperties;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Scheduler\Model\MessageSchedule;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class MessageScheduleTest extends \PHPUnit\Framework\TestCase
{
    private $router;
    private $fileProperties;
    private $coreParametersHelper;
    private $translatorMock;

    /**
     * @var Report
     */
    private $report;

    /**
     * @var MessageSchedule
     */
    private $messageSchedule;

    protected function setUp()
    {
        $this->router               = $this->createMock(Router::class);
        $this->fileProperties       = $this->createMock(FileProperties::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->translatorMock       = $this->createMock(TranslatorInterface::class);
        $this->report               = new Report();
        $this->messageSchedule      = new MessageSchedule(
            $this->translatorMock,
            $this->fileProperties,
            $this->coreParametersHelper,
            $this->router
        );
    }

    /**
     * @dataProvider sendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testSendFile($fileSize, $limit)
    {
        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message')
            ->willReturn('Subject');

        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_report_view')
            ->willReturn('some/route');

        $this->messageSchedule->getMessage($this->report, 'path-to-a-file');
    }

    public function sendFileProvider()
    {
        return [
            [10, 100],
            [100, 100],
            [1, 1],
            [1, 1],
        ];
    }

    /**
     * @dataProvider doSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testDoSendFile($fileSize, $limit)
    {
        $this->translatorMock->expects($this->once())
            ->method('trans')
            ->with('mautic.report.schedule.email.message_file_not_attached')
            ->willReturn('Subject');

        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_report_view');

        $this->messageSchedule->getMessage($this->report, 'path-to-a-file');
    }

    public function doSendFileProvider()
    {
        return [
            [100, 10],
            [100, 99],
        ];
    }

    /**
     * @dataProvider sendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testFileCouldBeSend($fileSize, $limit)
    {
        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $this->assertTrue($this->messageSchedule->fileCouldBeSend('path-to-a-file'));
    }

    /**
     * @dataProvider doSendFileProvider
     *
     * @param int $fileSize
     * @param int $limit
     */
    public function testFileCouldNotBeSend($fileSize, $limit)
    {
        $this->fileProperties->expects($this->once())
            ->method('getFileSize')
            ->with('path-to-a-file')
            ->willReturn($fileSize);

        $this->coreParametersHelper->expects($this->once())
            ->method('get')
            ->with('report_export_max_filesize_in_bytes')
            ->willReturn($limit);

        $this->assertFalse($this->messageSchedule->fileCouldBeSend('path-to-a-file'));
    }
}
