<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\EventListener\AssetImportExportSubscriber;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AssetImportExportSubscriberTest extends TestCase
{
    private AssetImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&AssetModel $assetModel;
    private EventDispatcher $eventDispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManager::class);
        $this->assetModel      = $this->createMock(AssetModel::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);
        $this->subscriber      = new AssetImportExportSubscriber(
            $this->assetModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper
        );
        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    public function testAssetExport(): void
    {
        $asset = $this->createMock(Asset::class);
        $asset->method('getId')->willReturn(1);
        $asset->method('isPublished')->willReturn(true);
        $asset->method('getTitle')->willReturn('Test Asset');
        $asset->method('getDescription')->willReturn('Description');
        $asset->method('getAlias')->willReturn('test-alias');

        $this->assetModel->method('getEntity')->willReturn($asset);

        $event = new EntityExportEvent(Asset::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Asset::ENTITY_NAME, $exportedData);
        if (isset($exportedData[Asset::ENTITY_NAME]) && count($exportedData[Asset::ENTITY_NAME]) > 0) {
            $this->assertSame(1, $exportedData[Asset::ENTITY_NAME][0]['id']);
            $this->assertSame('Test Asset', $exportedData[Asset::ENTITY_NAME][0]['title']);
        }
    }

    public function testAssetImport(): void
    {
        $eventData = [
            [
                'id'                 => 1,
                'title'              => 'New Asset',
                'is_published'       => true,
                'description'        => 'Imported description',
                'alias'              => 'new-alias',
                'storage_location'   => 'local',
                'path'               => 'path/to/asset',
                'remote_path'        => '',
                'original_file_name' => 'file.pdf',
                'mime'               => 'application/pdf',
                'size'               => '1024',
                'disallow'           => false,
                'extension'          => 'pdf',
                'lang'               => 'en',
                'publish_up'         => null,
                'publish_down'       => null,
            ],
        ];

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(Asset::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onAssetImport($event);
    }
}
