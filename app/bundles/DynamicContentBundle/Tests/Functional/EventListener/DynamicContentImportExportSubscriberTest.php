<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\EventListener\DynamicContentImportExportSubscriber;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class DynamicContentImportExportSubscriberTest extends TestCase
{
    private DynamicContentImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&DynamicContentModel $dynamicContentModel;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private MockObject&DenormalizerInterface $serializer;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager       = $this->createMock(EntityManager::class);
        $this->dynamicContentModel = $this->createMock(DynamicContentModel::class);
        $this->auditLogModel       = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper      = $this->createMock(IpLookupHelper::class);
        $this->serializer          = $this->createMock(DenormalizerInterface::class);

        $this->subscriber = new DynamicContentImportExportSubscriber(
            $this->dynamicContentModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->serializer
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    private function createMockDynamicContent(): MockObject
    {
        $content = $this->createMock(DynamicContent::class);
        $content->method('getId')->willReturn(1);
        $content->method('getName')->willReturn('Test Dynamic Content');
        $content->method('getContent')->willReturn('<p>Test Content</p>');
        $content->method('getUuid')->willReturn('uuid-123');

        return $content;
    }

    public function testDynamicContentExport(): void
    {
        $mockContent = $this->createMockDynamicContent();
        $this->dynamicContentModel->method('getEntity')->with(1)->willReturn($mockContent);

        $event = new EntityExportEvent(DynamicContent::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(DynamicContent::ENTITY_NAME, $exportedData);
        $this->assertNotEmpty($exportedData[DynamicContent::ENTITY_NAME]);

        $exportedContent = reset($exportedData[DynamicContent::ENTITY_NAME]);
        $this->assertSame(1, $exportedContent['id']);
        $this->assertSame('Test Dynamic Content', $exportedContent['name']);
    }

    public function testDynamicContentImport(): void
    {
        $eventData = [
            DynamicContent::ENTITY_NAME => [
                [
                    'id'           => 1,
                    'name'         => 'New Dynamic Content',
                    'is_published' => true,
                    'content'      => '<p>Imported content</p>',
                    'publish_up'   => null,
                    'publish_down' => null,
                    'utm_tags'     => [],
                ],
            ],
        ];

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (DynamicContent $content) {
                return 'New Dynamic Content' === $content->getName()
                       && '<p>Imported content</p>' === $content->getContent()
                       && true === $content->isPublished();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(DynamicContent::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onImport($event);
    }
}
