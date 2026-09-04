<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\EventListener;

use Mautic\AssetBundle\EventListener\DashboardSubscriber;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class DashboardSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private AssetModel&MockObject $assetModel;

    private RouterInterface&MockObject $router;

    private CorePermissions&MockObject $permissions;

    private CacheProviderTagAwareInterface&MockObject $cacheProvider;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assetModel  = $this->createMock(AssetModel::class);
        $this->router      = $this->createMock(RouterInterface::class);
        $this->permissions = $this->createMock(CorePermissions::class);
        $this->cacheProvider = $this->createMock(CacheProviderTagAwareInterface::class);
        $this->translator  = new class() implements TranslatorInterface {
            public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return (string) $id;
            }

            public function getLocale(): string
            {
                return 'en';
            }
        };

        $cacheItem = new CacheItem();
        $cacheKeyProperty = new \ReflectionProperty(CacheItem::class, 'key');
        $cacheKeyProperty->setValue($cacheItem, 'asset-dashboard-test');
        $taggableProperty = new \ReflectionProperty(CacheItem::class, 'isTaggable');
        $taggableProperty->setValue($cacheItem, true);
        $this->cacheProvider->method('getItem')->willReturn($cacheItem);
    }

    public function testDownloadsInTimePassesFilterArrayAndPermissionFlagToAssetModel(): void
    {
        $dateFrom = new \DateTime('2026-06-01 00:00:00', new \DateTimeZone('UTC'));
        $dateTo   = new \DateTime('2026-07-01 23:59:59', new \DateTimeZone('UTC'));
        $filter   = [];

        $widget = new Widget();
        $widget->setType('asset.downloads.in.time');
        $widget->setHeight(300);
        $widget->setParams([
            'timeUnit'   => 'd',
            'dateFrom'   => $dateFrom,
            'dateTo'     => $dateTo,
            'dateFormat' => null,
            'filter'     => $filter,
        ]);

        $event = new WidgetDetailEvent($this->translator, $this->permissions, $widget, $this->cacheProvider);

        $this->permissions->method('isGranted')->willReturnCallback(static function (mixed $permission, mixed $mode = null): mixed {
            if (is_array($permission)) {
                return [true];
            }

            return true;
        });

        $this->assetModel->expects($this->once())
            ->method('getDownloadsLineChartData')
            ->with('d', $dateFrom, $dateTo, null, $filter, true)
            ->willReturn([
                'labels'   => ['2026-06-01'],
                'datasets' => [['label' => 'downloads', 'data' => [1]]],
            ]);

        $subscriber = new DashboardSubscriber($this->assetModel, $this->router);
        $subscriber->onWidgetDetailGenerate($event);

        $templateData = $event->getTemplateData();
        $this->assertArrayHasKey('chartData', $templateData);
    }

    public function testUniqueVsRepetitiveDownloadsPassesFilterArrayAndPermissionFlagToAssetModel(): void
    {
        $dateFrom = new \DateTime('2026-06-01 00:00:00', new \DateTimeZone('UTC'));
        $dateTo   = new \DateTime('2026-07-01 23:59:59', new \DateTimeZone('UTC'));

        $widget = new Widget();
        $widget->setType('unique.vs.repetitive.downloads');
        $widget->setHeight(300);
        $widget->setParams([
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'filter'   => [],
        ]);

        $event = new WidgetDetailEvent($this->translator, $this->permissions, $widget, $this->cacheProvider);

        $this->permissions->method('isGranted')->willReturnCallback(static function (mixed $permission, mixed $mode = null): mixed {
            if (is_array($permission)) {
                return [true];
            }

            return true;
        });

        $this->assetModel->expects($this->once())
            ->method('getUniqueVsRepetitivePieChartData')
            ->with($dateFrom, $dateTo, [], true)
            ->willReturn([
                'labels'   => ['unique', 'repetitive'],
                'datasets' => [['data' => [1, 0]]],
            ]);

        $subscriber = new DashboardSubscriber($this->assetModel, $this->router);
        $subscriber->onWidgetDetailGenerate($event);

        $templateData = $event->getTemplateData();
        $this->assertArrayHasKey('chartData', $templateData);
    }

    public function testPopularAssetsPassesFilterArrayAndPermissionFlagToAssetModel(): void
    {
        $dateFrom = new \DateTime('2026-06-01 00:00:00', new \DateTimeZone('UTC'));
        $dateTo   = new \DateTime('2026-07-01 23:59:59', new \DateTimeZone('UTC'));

        $widget = new Widget();
        $widget->setType('popular.assets');
        $widget->setHeight(300);
        $widget->setParams([
            'dateFrom' => $dateFrom,
            'dateTo'   => $dateTo,
            'limit'    => 3,
            'filter'   => [],
        ]);

        $event = new WidgetDetailEvent($this->translator, $this->permissions, $widget, $this->cacheProvider);

        $this->permissions->method('isGranted')->willReturnCallback(static function (mixed $permission, mixed $mode = null): mixed {
            if (is_array($permission)) {
                return [true];
            }

            return true;
        });

        $this->assetModel->expects($this->once())
            ->method('getPopularAssets')
            ->with(3, $dateFrom, $dateTo, [], true)
            ->willReturn([
                [
                    'id'             => 1,
                    'title'          => 'Asset 1',
                    'download_count' => 2,
                ],
            ]);

        $this->router->method('generate')->willReturn('/s/assets/view/1');

        $subscriber = new DashboardSubscriber($this->assetModel, $this->router);
        $subscriber->onWidgetDetailGenerate($event);

        $templateData = $event->getTemplateData();
        $this->assertArrayHasKey('bodyItems', $templateData);
    }
}
