<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\AssetBundle\EventListener\AssetImportExportSubscriber;
use Mautic\AssetBundle\Model\AssetModel;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AssetImportExportSubscriberTest extends TestCase
{
    private AssetImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&AssetModel $assetModel;
    private EventDispatcher $eventDispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private MockObject&DenormalizerInterface $serializer;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManager::class);
        $this->assetModel      = $this->createMock(AssetModel::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);
        $this->serializer      = $this->createMock(DenormalizerInterface::class);

        $assetRepository = $this->createMock(\Doctrine\Persistence\ObjectRepository::class);
        $assetRepository->method('findOneBy')->willReturn(null);

        $this->entityManager
            ->method('getRepository')
            ->with(Asset::class)
            ->willReturn($assetRepository);

        $this->subscriber      = new AssetImportExportSubscriber(
            $this->assetModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->serializer
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
        $exportedAsset = reset($exportedData[Asset::ENTITY_NAME]);

        if (isset($exportedData[Asset::ENTITY_NAME]) && count($exportedData[Asset::ENTITY_NAME]) > 0) {
            $this->assertSame(1, $exportedAsset['id']);
            $this->assertSame('Test Asset', $exportedAsset['title']);
        }
    }
}
