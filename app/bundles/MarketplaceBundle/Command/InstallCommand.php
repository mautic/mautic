<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\MarketplaceBundle\Service\Composer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    public const NAME = 'mautic:marketplace:install';

    private Composer $composer;

    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
        $this->setDescription('Installs a plugin that is available at Packagist.org');
        $this->addArgument('package', InputArgument::REQUIRED, 'The Packagist package to install (e.g. mautic/example-plugin)');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Installing '.$input->getArgument('package').', this might take a while...');

        // TODO check if the given package name is of type mautic-plugin
        $this->composer->install($input->getArgument('package'));

        $output->writeln('All done! '.$input->getArgument('package').' has successfully been installed.');
    }
}
