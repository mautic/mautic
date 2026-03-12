<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Mautic\LeadBundle\Event\CompanySegmentEvent;
use Mautic\LeadBundle\Event\CompanySegmentPostDelete;
use Mautic\LeadBundle\Event\CompanySegmentPostSave;
use Mautic\LeadBundle\Event\CompanySegmentPreDelete;
use Mautic\LeadBundle\Event\CompanySegmentPreSave;
use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Tests\Fixtures\CompanySegmentModelStub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class CompanySegmentModelTest extends TestCase
{
    public function testAliasIsUnique(): void
    {
        $id    = null;
        $alias = 'alias';
        $model = $this->getMockBuilder(CompanySegmentModel::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['getRepository', 'setTimestamps', 'cleanAlias', 'dispatchEvent'])
            ->getMock();

        $companySegment = $this->createMock(CompanySegment::class);
        $companySegment->method('getId')
            ->willReturn($id); // test $isNew parameter is properly passed
        $companySegment->method('getAlias')
            ->willReturn($alias);
        $companySegment->expects(self::once())
            ->method('setAlias')
            ->with($alias);

        $companySegmentRepository = $this->createMock(CompanySegmentRepository::class);
        $companySegmentRepository->expects(self::once())
            ->method('getSegments')
            ->with(null, $alias, $id)
            ->willReturn([]);

        $model->expects(self::once())
            ->method('setTimestamps')
            ->with($companySegment, true, true);
        $model->method('cleanAlias')
            ->willReturn($alias);
        $model->method('getRepository')
            ->willReturn($companySegmentRepository);
        $model->expects(self::exactly(2))
            ->method('dispatchEvent')
            ->willReturnMap([
                ['pre_save', $companySegment, true, null, null],
                ['post_save', $companySegment, true, null, null],
            ]);

        $model->saveEntity($companySegment);
    }

    public function testAliasIsNotUnique(): void
    {
        $id    = 12345745745;
        $alias = 'alias';
        $model = $this->getMockBuilder(CompanySegmentModel::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods(['getRepository', 'setTimestamps', 'cleanAlias', 'dispatchEvent'])
            ->getMock();

        $companySegment = $this->createMock(CompanySegment::class);
        $companySegment->method('getId')
            ->willReturn($id); // test $isNew parameter is properly passed
        $companySegment->method('getAlias')
            ->willReturn($alias);
        $companySegment->expects(self::once())
            ->method('setAlias')
            ->with($alias.'1');

        $companySegmentRepository = $this->createMock(CompanySegmentRepository::class);
        $companySegmentRepository->expects(self::exactly(2))
            ->method('getSegments')
            ->willReturnMap([
                [null, $alias, $id, [['id' => 1, 'name' => 'the name', 'alias' => 'the alias']]],
                [null, $alias.'1', $id, []],
            ]);

        $model->expects(self::once())
            ->method('setTimestamps')
            ->with($companySegment, false, true);
        $model->method('cleanAlias')
            ->willReturn($alias);
        $model->method('getRepository')
            ->willReturn($companySegmentRepository);
        $model->expects(self::exactly(2))
            ->method('dispatchEvent')
            ->willReturnMap([
                ['pre_save', $companySegment, false, null, null],
                ['post_save', $companySegment, false, null, null],
            ]);

        $model->saveEntity($companySegment);
    }

    public function testEventsRequireCompanySegments(): void
    {
        $model = $this->getMockBuilder(CompanySegmentModelStub::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods([])
            ->getMock();

        $this->expectException(MethodNotAllowedHttpException::class);
        $this->expectExceptionMessage('Entity must be of class CompanySegment()');

        $model->testDispatchEvent('any', $this->createMock(FormEntity::class));
    }

    /**
     * @dataProvider provideActionAndClass
     */
    public function testDoesNotHaveAnEvent(string $action, string $eventClass): void
    {
        $model = $this->getMockBuilder(CompanySegmentModelStub::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods([])
            ->getMock();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with($eventClass)
            ->willReturn(false);

        $model->setDispatcher($dispatcher);
        $result = $model->testDispatchEvent($action, $this->createMock(CompanySegment::class));
        self::assertNull($result);
    }

    public static function provideActionAndClass(): \Generator
    {
        yield 'pre save' => [
            'pre_save',
            CompanySegmentPreSave::class,
        ];

        yield 'post save' => [
            'post_save',
            CompanySegmentPostSave::class,
        ];

        yield 'pre delete' => [
            'pre_delete',
            CompanySegmentPreDelete::class,
        ];

        yield 'post delete' => [
            'post_delete',
            CompanySegmentPostDelete::class,
        ];
    }

    /**
     * @dataProvider provideExistingEvents
     */
    public function testEventsCallsEventFromAction(string $action, string $eventClass, ?bool $isNew, ?bool $expectedIsNew): void
    {
        $model = $this->getMockBuilder(CompanySegmentModelStub::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods([])
            ->getMock();

        $em = $this->createMock(EntityManagerInterface::class);

        $companySegment = $this->createMock(CompanySegment::class);

        if (null !== $isNew) {
            $providedEvent = new $eventClass($companySegment, $em, $isNew);
            $expectedEvent = new $eventClass($companySegment, $em, $expectedIsNew);
        } else {
            $providedEvent = new $eventClass($companySegment, $em);
            $expectedEvent = new $eventClass($companySegment, $em);
            $isNew         = false; // To prevent type error. The class is anyway tested.
        }

        \assert($providedEvent instanceof CompanySegmentEvent);
        \assert($expectedEvent instanceof CompanySegmentEvent);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($providedEvent)
            ->willReturnArgument(0);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with($eventClass)
            ->willReturn(true);

        $model->setDispatcher($dispatcher);
        $model->setEntityManger($em);
        $returnedEvent = $model->testDispatchEvent($action, $companySegment, $isNew, null);
        self::assertEquals($expectedEvent, $returnedEvent);
        self::assertSame($companySegment, $providedEvent->getCompanySegment());
        self::assertSame($em, $providedEvent->getEntityManager());
    }

    public static function provideExistingEvents(): \Generator
    {
        yield 'pre_save_is_new' => ['pre_save', CompanySegmentPreSave::class, true, true];
        yield 'pre_save_not_new' => ['pre_save', CompanySegmentPreSave::class, false, false];
        yield 'post_save_is_new' => ['post_save', CompanySegmentPostSave::class, true, true];
        yield 'post_save_not_new' => ['post_save', CompanySegmentPostSave::class, false, false];
        yield 'pre_delete' => ['pre_delete', CompanySegmentPreDelete::class, null, null];
        yield 'post_delete' => ['post_delete', CompanySegmentPostDelete::class, null, null];
    }

    public function testEventsCallsEventNotProvidedAndClassDoesNotExist(): void
    {
        $action = 'whatever';
        $model  = $this->getMockBuilder(CompanySegmentModelStub::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods([])
            ->getMock();

        $em = $this->createMock(EntityManagerInterface::class);

        $companySegment = $this->createMock(CompanySegment::class);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())
            ->method('dispatch');
        $dispatcher->expects(self::never())
            ->method('hasListeners');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Either the Event or proper action should be provided.');

        $model->setDispatcher($dispatcher);
        $model->setEntityManger($em);
        $model->testDispatchEvent($action, $companySegment, true, null);
        self::fail('After exception.');
    }

    public function testEventsCallsWithProvidedEvent(): void
    {
        $action = 'whatever';
        $model  = $this->getMockBuilder(CompanySegmentModelStub::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->onlyMethods([])
            ->getMock();

        $em = $this->createMock(EntityManagerInterface::class);

        $companySegment = $this->createMock(CompanySegment::class);
        $providedEvent  = $this->createMock(CompanySegmentEvent::class);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with($providedEvent)
            ->willReturnArgument(0);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with($providedEvent::class)
            ->willReturn(true);

        $model->setDispatcher($dispatcher);
        $model->setEntityManger($em);
        $returnedEvent = $model->testDispatchEvent($action, $companySegment, true, $providedEvent);
        self::assertSame($providedEvent, $returnedEvent);
    }
}
