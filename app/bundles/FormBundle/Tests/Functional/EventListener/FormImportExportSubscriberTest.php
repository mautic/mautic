<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\EventListener\FormImportExportSubscriber;
use Mautic\FormBundle\Model\FormModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class FormImportExportSubscriberTest extends TestCase
{
    private FormImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&FormModel $formModel;
    private EventDispatcher $eventDispatcher;
    private MockObject&AuditLogModel $auditLogModel;
    private MockObject&IpLookupHelper $ipLookupHelper;
    private MockObject&EventDispatcherInterface $dispatcher;
    private MockObject&DenormalizerInterface $serializer;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManager::class);
        $this->formModel       = $this->createMock(FormModel::class);
        $this->auditLogModel   = $this->createMock(AuditLogModel::class);
        $this->ipLookupHelper  = $this->createMock(IpLookupHelper::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->serializer      = $this->createMock(DenormalizerInterface::class);

        $this->subscriber = new FormImportExportSubscriber(
            $this->entityManager,
            $this->formModel,
            $this->auditLogModel,
            $this->ipLookupHelper,
            $this->dispatcher,
            $this->serializer,
        );

        $this->eventDispatcher = new EventDispatcher();
        $this->eventDispatcher->addSubscriber($this->subscriber);
    }

    public function testFormExport(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('getId')->willReturn(1);
        $form->method('getName')->willReturn('Test Form');
        $form->method('isPublished')->willReturn(true);
        $form->method('getDescription')->willReturn('Test Description');
        $form->method('getAlias')->willReturn('test-alias');
        $form->method('getUuid')->willReturn('uuid-123');

        $this->formModel->method('getEntity')->willReturn($form);

        $event = new EntityExportEvent(Form::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Form::ENTITY_NAME, $exportedData);
        $this->assertNotEmpty($exportedData[Form::ENTITY_NAME]);

        $firstItem = reset($exportedData[Form::ENTITY_NAME]);
        $this->assertSame(1, $firstItem['id']);
        $this->assertSame('Test Form', $firstItem['name']);
    }

    public function testFormImport(): void
    {
        $eventData = [
            Form::ENTITY_NAME => [
                [
                    'id'           => 1,
                    'name'         => 'New Form',
                    'is_published' => true,
                    'description'  => 'Imported description',
                    'alias'        => 'new-alias',
                    'cached_html'  => '<div>Form HTML</div>',
                    'post_action'  => 'redirect',
                    'template'     => 'default',
                    'form_type'    => 'standard',
                    'render_style' => 'normal',
                ],
            ],
        ];

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->atLeastOnce())->method('flush');

        $event = new EntityImportEvent(Form::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onFormImport($event);
    }
}
