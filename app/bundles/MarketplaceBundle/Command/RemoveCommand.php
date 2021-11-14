<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\MarketplaceBundle\Service\Composer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends Command
{
    public const NAME = 'mautic:marketplace:remove';

    private Composer $composer;

    public function __construct(Composer $composer)
    {
        parent::__construct();
        $this->composer = $composer;
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
        $this->setDescription('Removes a plugin that is currently installed');
        $this->addArgument('package', InputArgument::REQUIRED, 'The Packagist package of the plugin to remove (e.g. mautic/example-plugin)');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Removing '.$input->getArgument('package').', this might take a while...');

        // TODO check if the given package name is of type mautic-plugin
        $this->composer->remove($input->getArgument('package'));

        $output->writeln($input->getArgument('package').' has successfully been removed.');

        // TODO return actual status code
        return 0;
    }
}
