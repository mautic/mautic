<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand as MigrateCommand;
use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Update\Step\UpdateSchemaStep;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class UpdateSchemaStepTest extends AbstractStepTestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject&MigrateCommand
     */
    private MockObject $migrateCommand;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcher;

    private UpdateSchemaStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator     = $this->createMock(TranslatorInterface::class);
        $kernel               = $this->createMock(KernelInterface::class);
        $kernel
            ->method('getBundles')
            ->willReturn([]);

        $this->migrateCommand = $this->createMock(MigrateCommand::class);
        $this->migrateCommand->method('isEnabled')
            ->willReturn(true);
        $this->migrateCommand->method('getName')
            ->willReturn('doctrine:migrations:migrate');
        $this->migrateCommand->method('getAliases')
            ->willReturn([]);
        $this->migrateCommand->method('getHelperSet')
            ->willReturn($this->createStub(HelperSet::class));

        $definition = $this->createMock(InputDefinition::class);
        $definition->method('hasArgument')
            ->willReturn(true);
        $inputArgument = $this->createMock(InputArgument::class);
        $inputArgument->method('getName')
            ->willReturn('');
        $inputArgument->method('isArray')
            ->willReturn(false);
        $definition->method('getArgument')
            ->willReturn($inputArgument);
        $this->migrateCommand->method('getDefinition')
            ->willReturn($definition);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var ContainerInterface|MockObject $container */
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willReturnMap([
                ['kernel', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $kernel],
                ['event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher],
                ['doctrine:migrations:migrate', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->migrateCommand],
            ]);
        $container->method('hasParameter')
            ->willReturnMap([
                ['console.command.ids', true],
                ['console.lazy_command.ids', false],
            ]);

        $container->expects($this->once())->method('getParameter')
            ->with('console.command.ids')
            ->willReturn(
                ['doctrine:migrations:migrate']
            );

        $kernel->method('getContainer')
            ->willReturn($container);

        $this->step = new UpdateSchemaStep($this->translator, $kernel);
    }

    public function testUpdateFailedExceptionThrownIfMigrationsFailed(): void
    {
        $this->expectException(UpdateFailedException::class);

        $this->migrateCommand->expects($this->once())->method('run')
            ->willReturn(1);

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->willReturnCallback(
                function (ConsoleEvent $event, string $eventName): ConsoleEvent {
                    if ($event instanceof ConsoleCommandEvent) {
                        $event->enableCommand();
                    }

                    return $event;
                }
            );

        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testExceptionNotThrownIfMigrationsWereSuccessful(): void
    {
        $this->migrateCommand->expects($this->once())->method('run')
            ->willReturn(0);

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch')
            ->willReturnCallback(
                function (ConsoleEvent $event, string $eventName): ConsoleEvent {
                    if ($event instanceof ConsoleCommandEvent) {
                        $event->enableCommand();
                    }

                    return $event;
                }
            );

        $this->translator
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }
}
