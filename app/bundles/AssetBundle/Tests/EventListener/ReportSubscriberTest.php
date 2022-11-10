<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\AssetBundle\EventListener\ReportSubscriber;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ChannelListHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $channelListHelper;

    /**
     * @var CompanyReportData|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyReportData;

    /**
     * @var DownloadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $downloadRepository;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryBuilder;

    public function setUp(): void
    {
        $this->queryBuilder       = $this->createMock(QueryBuilder::class);
        $this->channelListHelper  = $this->createMock(ChannelListHelper::class);
        $this->companyReportData  = $this->createMock(CompanyReportData::class);
        $this->downloadRepository = $this->createMock(DownloadRepository::class);
    }

    public function testOnReportBuilderWithUnknownContext(): void
    {
        $companyReportData = new class() extends CompanyReportData {
            public function __construct()
            {
            }
        };

        $downloadRepository = new class() extends DownloadRepository {
            public function __construct()
            {
            }
        };

        $event = new class() extends ReportBuilderEvent {
            public function __construct()
            {
                $this->context = 'unicorn';
            }
        };

        $reportSubscriber = new ReportSubscriber($companyReportData, $downloadRepository);

        $reportSubscriber->onReportBuilder($event);

        Assert::assertSame([], $event->getTables());
    }

    public function testOnReportBuilderWithAssetDownloadContext(): void
    {
        $companyReportData = new class() extends CompanyReportData {
            public function __construct()
            {
            }

            /**
             * @return array<mixed>
             */
            public function getCompanyData(): array
            {
                return [];
            }
        };

        $downloadRepository = new class() extends DownloadRepository {
            public function __construct()
            {
            }
        };

        $channelListHelper = new class() extends ChannelListHelper {
            public function __construct()
            {
            }
        };

        $reportHelper = new class() extends ReportHelper {
            public function __construct()
            {
            }
        };

        $event = new ReportBuilderEvent($this->createTranslatorMock(), $channelListHelper, ReportSubscriber::CONTEXT_ASSET_DOWNLOAD, [], $reportHelper);

        $reportSubscriber = new ReportSubscriber($companyReportData, $downloadRepository);

        $reportSubscriber->onReportBuilder($event);

        Assert::assertSame(
            [
                'alias' => 'download_count',
                'label' => '[trans]mautic.asset.report.download_count[/trans]',
                'type'  => 'int',
            ],
            $event->getTables()['assets']['columns']['a.download_count']
        );

        Assert::assertSame(
            [
                'alias' => 'unique_download_count',
                'label' => '[trans]mautic.asset.report.unique_download_count[/trans]',
                'type'  => 'int',
            ],
            $event->getTables()['assets']['columns']['a.unique_download_count']
        );

        Assert::assertSame(
            [
                'alias'   => 'download_count',
                'label'   => '[trans]mautic.asset.report.download_count[/trans]',
                'type'    => 'int',
                'formula' => 'COUNT(ad.id)',
            ],
            $event->getTables()['asset.downloads']['columns']['a.download_count']
        );

        Assert::assertSame(
            [
                'alias'   => 'unique_download_count',
                'label'   => '[trans]mautic.asset.report.unique_download_count[/trans]',
                'type'    => 'int',
                'formula' => 'COUNT(DISTINCT ad.lead_id)',
            ],
            $event->getTables()['asset.downloads']['columns']['a.unique_download_count']
        );
    }

    private function createTranslatorMock(): TranslatorInterface
    {
        return new class() implements TranslatorInterface {
            /**
             * @param array<int|string> $parameters
             */
            public function trans($id, array $parameters = [], $domain = null, $locale = null): string
            {
                return '[trans]'.$id.'[/trans]';
            }

            /**
             * @param array<int|string> $parameters
             *
             * @return string
             */
            public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function setLocale($locale): void
            {
            }

            public function getLocale()
            {
                return '';
            }
        };
    }

    public function testGroupByDefaultConfigured(): void
    {
        $report             = new Report();
        $report->setSource(ReportSubscriber::CONTEXT_ASSET_DOWNLOAD);
        $event              = new ReportGeneratorEvent($report, [], $this->queryBuilder, $this->channelListHelper);
        $subscriber         = new ReportSubscriber($this->companyReportData, $this->downloadRepository);
        $this->queryBuilder->method('from')->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('groupBy')
            ->with('ad.id');

        $this->assertFalse($event->hasGroupBy());

        $subscriber->onReportGenerate($event);
    }

    public function testGroupByNotDefaultConfigured(): void
    {
        $report             = new Report();
        $report->setSource(ReportSubscriber::CONTEXT_ASSET_DOWNLOAD);
        $this->queryBuilder->method('from')->willReturn($this->queryBuilder);
        $report->setGroupBy(['a.id' => 'desc']);
        $event              = new ReportGeneratorEvent($report, [], $this->queryBuilder, $this->channelListHelper);
        $subscriber         = new ReportSubscriber($this->companyReportData, $this->downloadRepository);
        $subscriber->onReportGenerate($event);
        $this->assertTrue($event->hasGroupBy());
    }
}
