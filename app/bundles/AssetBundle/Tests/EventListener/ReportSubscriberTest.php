<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\AssetBundle\EventListener\ReportSubscriber;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private ChannelListHelper $channelListHelper;

    /**
     * @var CompanyReportData|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $companyReportData;

    /**
     * @var DownloadRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $downloadRepository;

    /**
     * @var QueryBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $queryBuilder;

    private ReportHelper $reportHelper;

    public function setUp(): void
    {
        $this->queryBuilder       = $this->createMock(QueryBuilder::class);
        $this->channelListHelper  = new ChannelListHelper($this->createMock(EventDispatcherInterface::class), $this->createMock(Translator::class));
        $this->reportHelper       = new ReportHelper($this->createMock(EventDispatcherInterface::class));
        $this->companyReportData  = $this->createMock(CompanyReportData::class);
        $this->downloadRepository = $this->createMock(DownloadRepository::class);
    }

    public function testOnReportBuilderWithUnknownContext(): void
    {
        $companyReportData = new class extends CompanyReportData {
            public function __construct()
            {
            }
        };

        $downloadRepository = new class extends DownloadRepository {
            public function __construct()
            {
            }
        };

        $event = new class extends ReportBuilderEvent {
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
        $companyReportData = new class extends CompanyReportData {
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

        $downloadRepository = new class extends DownloadRepository {
            public function __construct()
            {
            }
        };

        $event = new ReportBuilderEvent($this->createTranslatorMock(), $this->channelListHelper, ReportSubscriber::CONTEXT_ASSET_DOWNLOAD, [], $this->reportHelper);

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
        return new class implements TranslatorInterface {
            /**
             * @param array<int|string> $parameters
             */
            public function trans(string $id, array $parameters = [], string $domain = null, string $locale = null): string
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function getLocale(): string
            {
                return 'en';
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
