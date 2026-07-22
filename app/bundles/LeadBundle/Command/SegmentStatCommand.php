<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Event\GetStatDataEvent;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'mautic:segments:stat',
    description: 'Gather Segment Statistics'
)]
final class SegmentStatCommand extends ModeratedCommand
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io         = new SymfonyStyle($input, $output);
        $event      = new GetStatDataEvent();
        $this->dispatcher->dispatch($event);

        if (empty($event->getResults())) {
            $io->write('There is no segment to show!!');
        } else {
            $io->table([
                'Title',
                'Id',
                'IsPublished',
                'IsUsed',
            ],
                $event->getResults()
            );
        }

        return Command::SUCCESS;
    }
}
