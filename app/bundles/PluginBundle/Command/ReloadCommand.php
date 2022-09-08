<?php

namespace Mautic\PluginBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReloadCommand extends \Symfony\Component\Console\Command\Command
{
    private \Mautic\PluginBundle\Facade\ReloadFacade $reloadFacade;

    public function __construct(\Mautic\PluginBundle\Facade\ReloadFacade $reloadFacade)
    {
        $this->reloadFacade = $reloadFacade;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeLn(
            $this->reloadFacade->reloadPlugins()
        );

        return 0;
    }
}
