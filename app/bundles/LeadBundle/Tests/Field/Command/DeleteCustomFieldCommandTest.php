<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Field\Command;

use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Field\BackgroundService;
use Mautic\LeadBundle\Field\Command\DeleteCustomFieldCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DeleteCustomFieldCommandTest extends TestCase
{
    /**
     * @var MockObject&BackgroundService
     */
    private MockObject $backgroundServiceMock;

    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translatorInterfaceMock;

    /**
     * @var MockObject&LeadFieldRepository
     */
    private MockObject $leadFieldRepository;

    private DeleteCustomFieldCommand $deleteCustomFieldCommand;

    protected function setUp(): void
    {
        $this->backgroundServiceMock    = $this->createMock(BackgroundService::class);
        $this->translatorInterfaceMock  = $this->createMock(TranslatorInterface::class);
        $this->leadFieldRepository      = $this->createMock(LeadFieldRepository::class);
        $this->deleteCustomFieldCommand = new DeleteCustomFieldCommand(
            $this->backgroundServiceMock,
            $this->translatorInterfaceMock,
            $this->leadFieldRepository,
        );
    }

    public function testExecute(): void
    {
        $this->backgroundServiceMock
            ->expects($this->once())
            ->method('deleteColumn')
            ->with(42, 0);
        $this->translatorInterfaceMock
            ->expects($this->once())
            ->method('trans')
            ->with('mautic.lead.field.column_was_deleted')
            ->willReturn('Column was deleted');
        $commandTester = new CommandTester($this->deleteCustomFieldCommand);
        $commandTester->execute([
            // pass arguments to the command
            '--id'   => '42',
            '--user' => '0',
        ]);

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Column was deleted', $output);
    }
}
