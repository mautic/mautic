<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Command;

use Mautic\ReportBundle\Scheduler\Command\ExportSchedulerCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MockExportSchedulerCommand extends ExportSchedulerCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        sleep(2);

        return parent::execute($input, $output);
    }
}
