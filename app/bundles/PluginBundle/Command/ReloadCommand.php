<?php

namespace Mautic\PluginBundle\Command;

use Mautic\PluginBundle\Facade\ReloadFacade;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadCommand extends Command
{
    private ReloadFacade $reloadFacade;

    public function __construct(ReloadFacade $reloadFacade)
    {
        parent::__construct();

        $this->reloadFacade = $reloadFacade;
    }

    protected function configure()
    {
        $this
            ->setName('mautic:plugins:reload')
            ->setAliases(
                [
                    'mautic:plugins:install',
                    'mautic:plugins:update',
                ]
            )
            ->setDescription('Installs, updates, enable and/or disable plugins.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeLn($this->reloadFacade->reloadPlugins());

        return 0;
    }
}
