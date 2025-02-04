<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Command\EntityExportCommand;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EntityExportCommandTest extends TestCase
{
    /** @var MockObject&EventDispatcherInterface */
    private MockObject $dispatcher;
    private PathsHelper $pathsHelper;
    private CoreParametersHelper $coreParametersHelper;
    private EntityExportCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        /** @var MockObject&EventDispatcherInterface $mockDispatcher */
        $mockDispatcher   = $this->createMock(EventDispatcherInterface::class);
        $this->dispatcher = $mockDispatcher;

        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        $this->command = new EntityExportCommand(
            $this->dispatcher,
            $this->pathsHelper,
            $this->coreParametersHelper
        );

        $application = new Application();
        $application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteFailsWithoutEntityOrId(): void
    {
        $this->commandTester->execute([
            '--entity' => '',
            '--id'     => '',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('You must specify the entity and at least one valid entity ID.', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testExecuteDispatchesEvent(): void
    {
        $entityName = 'campaign';
        $entityId   = 123;
        $mockEvent  = $this->createMock(EntityExportEvent::class);
        $mockEvent->method('getEntities')->willReturn(['id' => $entityId, 'name' => 'Test Campaign']);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityExportEvent::class), $entityName)
            ->willReturn($mockEvent);

        $this->commandTester->execute([
            '--entity'    => $entityName,
            '--id'        => (string) $entityId,
            '--json-only' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('"id": 123', $output);
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    public function testExecuteRequiresOutputOption(): void
    {
        $entityName = 'campaign';
        $entityId   = 123;

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityExportEvent::class), $entityName)
            ->willReturn(new EntityExportEvent($entityName, $entityId));

        $this->commandTester->execute([
            '--entity' => $entityName,
            '--id'     => (string) $entityId,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('You must specify one of --json-only, --json-file, or --zip-file options.', $output);
        $this->assertSame(1, $this->commandTester->getStatusCode());
    }

    public function testJsonFileOptionCreatesFile(): void
    {
        $entityName = 'campaign';
        $entityId   = 123;
        $mockEvent  = $this->createMock(EntityExportEvent::class);
        $mockEvent->method('getEntities')->willReturn(['id' => $entityId, 'name' => 'Test Campaign']);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityExportEvent::class), $entityName)
            ->willReturn($mockEvent);

        $this->commandTester->execute([
            '--entity'    => $entityName,
            '--id'        => (string) $entityId,
            '--json-file' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('JSON file created at:', $output);
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }

    public function testZipFileOptionCreatesZip(): void
    {
        $entityName = 'campaign';
        $entityId   = 123;
        $mockEvent  = $this->createMock(EntityExportEvent::class);
        $mockEvent->method('getEntities')->willReturn(['id' => $entityId, 'name' => 'Test Campaign']);

        $this->dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(EntityExportEvent::class), $entityName)
            ->willReturn($mockEvent);

        $this->commandTester->execute([
            '--entity'   => $entityName,
            '--id'       => (string) $entityId,
            '--zip-file' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('.zip', $output);
        $this->assertSame(0, $this->commandTester->getStatusCode());
    }
}
