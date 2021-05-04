<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\Entity\DownloadRepository;
use Mautic\AssetBundle\EventListener\ReportSubscriber;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\Assert;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
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

            public function getCompanyData()
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
            public function trans($id, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null)
            {
                return '[trans]'.$id.'[/trans]';
            }

            public function setLocale($locale)
            {
            }

            public function getLocale()
            {
            }
        };
    }
}
