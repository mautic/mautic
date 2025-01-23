<?php

namespace Mautic\CoreBundle\Tests\Unit\Update\Step;

use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand as MigrateCommand;
use Mautic\CoreBundle\Exception\UpdateFailedException;
use Mautic\CoreBundle\Update\Step\UpdateSchemaStep;
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

class UpdateSchemaStepTest extends AbstractStepTest
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private MockObject $translator;

    /**
     * @var MockObject|KernelInterface
     */
    private MockObject $kernel;

    /**
     * @var MockObject|MigrateCommand
     */
    private MockObject $migrateCommand;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $eventDispatcher;

    /**
     * @var MockObject&HelperSet
     */
    private MockObject $helperSet;

    private UpdateSchemaStep $step;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator     = $this->createMock(TranslatorInterface::class);
        $this->kernel         = $this->createMock(KernelInterface::class);
        $this->helperSet      = $this->createMock(HelperSet::class);
        $this->kernel
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
            ->willReturn($this->helperSet);

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
            ->will($this->returnValueMap([
                ['kernel', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->kernel],
                ['event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher],
                ['doctrine:migrations:migrate', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->migrateCommand],
            ]));
        $container->method('hasParameter')
            ->will($this->returnValueMap([
                ['console.command.ids', true],
                ['console.lazy_command.ids', false],
            ]));

        $container->method('getParameter')
            ->with('console.command.ids')
            ->willReturn(
                ['doctrine:migrations:migrate']
            );

        $this->kernel->method('getContainer')
            ->willReturn($container);

        $this->step = new UpdateSchemaStep($this->translator, $container);
    }

    public function testUpdateFailedExceptionThrownIfMigrationsFailed(): void
    {
        $this->expectException(UpdateFailedException::class);

        $this->migrateCommand->method('run')
            ->willReturn(1);

        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(
                function (ConsoleEvent $event, string $eventName) {
                    switch (true) {
                        case $event instanceof ConsoleCommandEvent:
                            $event->enableCommand();
                            break;
                    }

                    return $event;
                }
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        $this->step->execute($this->progressBar, $this->input, $this->output);
    }

    public function testExceptionNotThrownIfMigrationsWereSuccessful(): void
    {
        $this->migrateCommand->method('run')
            ->willReturn(0);

        $this->eventDispatcher->method('dispatch')
            ->willReturnCallback(
                function (ConsoleEvent $event, string $eventName) {
                    switch (true) {
                        case $event instanceof ConsoleCommandEvent:
                            $event->enableCommand();
                            break;
                    }

                    return $event;
                }
            );

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturn('');

        try {
            $this->step->execute($this->progressBar, $this->input, $this->output);
            $this->expectNotToPerformAssertions();
        } catch (UpdateFailedException) {
            $this->fail('UpdateFailedException should not have been thrown');
        }
    }
}
