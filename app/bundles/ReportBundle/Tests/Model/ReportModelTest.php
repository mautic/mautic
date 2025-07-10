<?php

namespace Mautic\ReportBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Model\ReportModel;
use Mautic\ReportBundle\Tests\Fixtures;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class ReportModelTest extends \PHPUnit\Framework\TestCase
{
    private ReportModel $reportModel;

    protected function setUp(): void
    {
        $fieldModelMock = $this->createMock(FieldModel::class);
        $fieldModelMock->method('getPublishedFieldArrays')->willReturn([]);

        $mockDispatcher = $this->createMock(EventDispatcher::class);
        $mockDispatcher->method('dispatch')
            ->willReturnCallback(
                function (ReportBuilderEvent $event) {
                    $reportBuilderData = Fixtures::getReportBuilderEventData();
                    $event->addTable('assets', $reportBuilderData['all']['tables']['assets']);

                    return $event;
                }
            );

        $translatorMock = $this->createMock(Translator::class);
        // Make the translator return whatever string is passed to it instead of null
        $translatorMock->method('trans')->withAnyParameters()->willReturnArgument(0);

        $this->reportModel = new ReportModel(
            $this->createMock(CoreParametersHelper::class),
            $this->createMock(Environment::class),
            new ChannelListHelper($this->createMock(EventDispatcherInterface::class), $this->createMock(Translator::class)),
            $fieldModelMock,
            new ReportHelper($this->createMock(EventDispatcherInterface::class)),
            $this->createMock(CsvExporter::class),
            $this->createMock(ExcelExporter::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $mockDispatcher,
            $this->createMock(UrlGeneratorInterface::class),
            $translatorMock,
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(RequestStack::class)
        );

        // Do this to build the initial set of data from the subscribers that get used in all other contexts
        $this->reportModel->buildAvailableReports('all');

        parent::setUp();
    }

    public function testGetColumnListWithContext(): void
    {
        $properContextFormat = 'assets';
        $actual              = $this->reportModel->getColumnList($properContextFormat);
        $expected            = Fixtures::getGoodColumnList();

        $this->assertEquals($expected->choices, $actual->choices);
        $this->assertEquals($expected->definitions, $actual->definitions);
    }
}
