<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\ProcessSignal\Exception\SignalCaughtException;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
use Mautic\LeadBundle\Command\ContactScheduledExportCommand;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Entity\ContactExportSchedulerRepository;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
final class ContactScheduledExportCommandTest extends TestCase
{
    public function testForSignalCaughtException(): void
    {
        $eventDispatcher             = $this->createMock(EventDispatcherInterface::class);

        $translator           = $this->createStub(TranslatorInterface::class);
        $coreParametersHelper = $this->createStub(CoreParametersHelper::class);
        $dateHelper           = new DateHelper(
            'F j, Y g:i a T',
            'D, M d',
            'F j, Y',
            'g:i a',
            $translator,
            $coreParametersHelper
        );

        $formatterHelper             = new FormatterHelper($dateHelper, $translator);
        $processSignalService        = $this->createStub(ProcessSignalService::class);

        $contactExportSchedulerRepository = $this->createMock(ContactExportSchedulerRepository::class);
        $contactExportSchedulerRepository->method('findBy')
            ->willReturn([new ContactExportScheduler()]);

        $eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new SignalCaughtException(1));

        $command = new class($eventDispatcher, $formatterHelper, $processSignalService, $contactExportSchedulerRepository) extends ContactScheduledExportCommand {
            public function getExecute(InputInterface $input, OutputInterface $output): int
            {
                return $this->execute($input, $output);
            }
        };

        $inputInterfaceMock  = $this->createMock(InputInterface::class);
        $outputInterfaceMock = $this->createStub(OutputInterface::class);

        $inputInterfaceMock->expects($this->once())->method('getOption')
            ->with('ids')
            ->willReturn(1);

        $this->assertSame(ExitCode::TERMINATED, $command->getExecute($inputInterfaceMock, $outputInterfaceMock));
    }
}
