<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Command\EntityExportCommand;
use Mautic\CoreBundle\Event\EntityExportEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class EntityExportCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private MockObject&EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->dispatcher     = $this->createMock(EventDispatcherInterface::class);
        $pathsHelper          = $container->get(PathsHelper::class);
        $coreParametersHelper = $container->get(CoreParametersHelper::class);

        $command = new EntityExportCommand($this->dispatcher, $pathsHelper, $coreParametersHelper);

        $this->commandTester = new CommandTester($command);
    }

    private function createMockEvent(string $entityName, int $entityId): EntityExportEvent
    {
        $mockEvent = new EntityExportEvent($entityName, $entityId);
        $mockEvent->addEntity($entityName, ['id' => $entityId, 'name' => 'Test Campaign']);

        return $mockEvent;
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

        $this->dispatcher->method('dispatch')->willReturnCallback(
            fn ($event) => $this->createMockEvent($entityName, $entityId)
        );

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

        $this->dispatcher->method('dispatch')->willReturnCallback(
            fn ($event) => $this->createMockEvent($entityName, $entityId)
        );

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

        $this->dispatcher->method('dispatch')->willReturnCallback(
            fn ($event) => $this->createMockEvent($entityName, $entityId)
        );

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

        $this->dispatcher->method('dispatch')->willReturnCallback(
            fn ($event) => $this->createMockEvent($entityName, $entityId)
        );

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
