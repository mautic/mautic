<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\EventListener\PageImportExportSubscriber;
use Mautic\PageBundle\Model\PageModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class PageImportExportSubscriberTest extends TestCase
{
    private PageImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&PageModel $pageModel;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private MockObject&DenormalizerInterface $serializer;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManager::class);
        $this->pageModel       = $this->createMock(PageModel::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);
        $this->serializer      = $this->createMock(DenormalizerInterface::class);

        $this->subscriber = new PageImportExportSubscriber(
            $this->pageModel,
            $this->entityManager,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->serializer
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    private function createMockPage(): MockObject
    {
        $page = $this->createMock(Page::class);
        $page->method('getId')->willReturn(1);
        $page->method('isPublished')->willReturn(true);
        $page->method('getTitle')->willReturn('Test Page');
        $page->method('getAlias')->willReturn('test-alias');

        return $page;
    }

    public function testPageExport(): void
    {
        $mockPage = $this->createMockPage();
        $this->pageModel->method('getEntity')->with(1)->willReturn($mockPage);

        $event = new EntityExportEvent(Page::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Page::ENTITY_NAME, $exportedData, 'Exported data must contain page entity.');
        $this->assertNotEmpty($exportedData[Page::ENTITY_NAME], 'Exported data for page should not be empty.');

        $exportedPages = $exportedData[Page::ENTITY_NAME] ?? [];
        $exportedPage  = array_values($exportedPages)[0] ?? [];

        $this->assertSame(1, $exportedPage['id'], 'Page ID mismatch.');
        $this->assertSame('Test Page', $exportedPage['title'], 'Page title mismatch.');
    }

    public function testPageImport(): void
    {
        $eventData = [
            Page::ENTITY_NAME => [
                [
                    'id'            => 1,
                    'title'         => 'New Page',
                    'is_published'  => true,
                    'alias'         => 'new-alias',
                    'content'       => '<p>Sample content</p>',
                    'publish_up'    => null,
                    'hits'          => 0,
                    'unique_hits'   => 0,
                    'variant_hits'  => 0,
                    'revision'      => 1,
                    'redirect_type' => '',
                    'redirect_url'  => '',
                    'publish_down'  => null,
                ],
            ],
        ];

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Page $page) {
                return 'New Page' === $page->getTitle()
                       && true === $page->isPublished()
                       && 'new-alias' === $page->getAlias();
            }));

        $this->entityManager->expects($this->once())->method('flush');

        $event = new EntityImportEvent(Page::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onPageImport($event);
    }
}
