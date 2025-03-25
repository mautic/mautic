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
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class DynamicContentImportExportSubscriberTest extends TestCase
{
    private DynamicContentImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&DynamicContentModel $dynamicContentModel;
    private MockObject&UserModel $userModel;
    private EventDispatcher $eventDispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;

    protected function setUp(): void
    {
        $this->entityManager       = $this->createMock(EntityManager::class);
        $this->dynamicContentModel = $this->createMock(DynamicContentModel::class);
        $this->userModel           = $this->createMock(UserModel::class);

        $this->subscriber = new DynamicContentImportExportSubscriber(
            $this->dynamicContentModel,
            $this->userModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
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

        return $content;
    }

    public function testDynamicContentExport(): void
    {
        $mockContent = $this->createMockDynamicContent();
        $this->dynamicContentModel->method('getEntity')->with(1)->willReturn($mockContent);

        $event = new EntityExportEvent(DynamicContent::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(DynamicContent::ENTITY_NAME, $exportedData, 'Exported data must contain dynamic content entity.');
        $this->assertNotEmpty($exportedData[DynamicContent::ENTITY_NAME], 'Exported data for dynamic content should not be empty.');

        $exportedContent = $exportedData[DynamicContent::ENTITY_NAME][0];
        $this->assertSame(1, $exportedContent['id'], 'Dynamic content ID mismatch.');
        $this->assertSame('Test Dynamic Content', $exportedContent['name'], 'Dynamic content name mismatch.');
    }

    public function testDynamicContentImport(): void
    {
        $eventData = [
            [
                'id'           => 1,
                'name'         => 'New Dynamic Content',
                'is_published' => true,
                'content'      => '<p>Imported content</p>',
                'publish_up'   => null,
                'publish_down' => null,
                'utm_tags'     => [],
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
