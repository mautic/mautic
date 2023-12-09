<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Facade\ReloadFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadCommand extends Command
{
    protected static $defaultName = 'mautic:plugins:reload';
    public function __construct(private ReloadFacade $reloadFacade)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setAliases(
                [
                    'mautic:plugins:install',
                    'mautic:plugins:update',
                ]
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeLn($this->reloadFacade->reloadPlugins());

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
    protected static $defaultDescription = 'Installs, updates, enable and/or disable plugins.';
}
