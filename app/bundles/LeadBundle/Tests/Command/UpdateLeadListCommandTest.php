<?php

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Symfony\Component\Console\Input\Input;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\DependencyInjection\Container;

class UpdateLeadListCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateLeadListsCommand
     */
    private $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandTester  = new UpdateLeadListsCommand();
        $this->commandTester->setContainer($this->createMock(Container::class));
    }

    public function testUpdateCommand(): void
    {
        $output = $this->commandTester->addOption('timing=1');

        $run = $output->run($this->createMock(Input::class), $this->createMock(Output::class));
        $this->assertEquals(
            0,
            $run
        );
    }
}
