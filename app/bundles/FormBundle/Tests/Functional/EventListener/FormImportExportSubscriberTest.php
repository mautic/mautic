<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Functional\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Event\EntityImportEvent;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\EventListener\FormImportExportSubscriber;
use Mautic\FormBundle\Model\FormModel;
use Mautic\UserBundle\Model\UserModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class FormImportExportSubscriberTest extends TestCase
{
    private FormImportExportSubscriber $subscriber;
    private MockObject&EntityManager $entityManager;
    private MockObject&FormModel $formModel;
    private MockObject&UserModel $userModel;
    private MockObject&Connection $connection;
    private MockObject&QueryBuilder $queryBuilder;
    private MockObject&Result $result;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->formModel     = $this->createMock(FormModel::class);
        $this->userModel     = $this->createMock(UserModel::class);
        $this->connection    = $this->createMock(Connection::class);
        $this->queryBuilder  = $this->createMock(QueryBuilder::class);
        $this->result        = $this->createMock(Result::class);

        $this->entityManager->method('getConnection')->willReturn($this->connection);
        $this->connection->method('createQueryBuilder')->willReturn($this->queryBuilder);
        $this->queryBuilder->method('select')->willReturnSelf();
        $this->queryBuilder->method('from')->willReturnSelf();
        $this->queryBuilder->method('where')->willReturnSelf();
        $this->queryBuilder->method('setParameter')->willReturnSelf();
        $this->queryBuilder->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAllAssociative')->willReturn([]);

        $this->subscriber = new FormImportExportSubscriber(
            $this->entityManager,
            $this->formModel,
            $this->userModel
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
        $form->method('getDescription')->willReturn('Description');

        $this->formModel->method('getEntity')->willReturn($form);

        $event = new EntityExportEvent(Form::ENTITY_NAME, 1);
        $this->eventDispatcher->dispatch($event);

        $exportedData = $event->getEntities();

        $this->assertArrayHasKey(Form::ENTITY_NAME, $exportedData);
        $this->assertSame(1, $exportedData[Form::ENTITY_NAME][0]['id']);
        $this->assertSame('Test Form', $exportedData[Form::ENTITY_NAME][0]['name']);
    }

    public function testFormImport(): void
    {
        $eventData = [
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
        ];

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->atLeastOnce())->method('flush');

        $event = new EntityImportEvent(Form::ENTITY_NAME, $eventData, 1);
        $this->subscriber->onFormImport($event);
    }
}
