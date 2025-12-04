<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Facade\ReloadFacade;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mautic:plugins:reload',
    description: 'Installs, updates, enable and/or disable plugins.',
    aliases: [
        'mautic:plugins:install',
        'mautic:plugins:update',
    ]
)]
class ReloadCommand
{
    public function __construct(
        private ReloadFacade $reloadFacade,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $output->writeLn($this->reloadFacade->reloadPlugins());

        return Command::SUCCESS;
    }
}
