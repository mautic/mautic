<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    public const NAME = 'mautic:marketplace:install';

    public function __construct(
        private ComposerHelper $composer,
        private PackageModel $packageModel
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
        $this->addArgument('package', InputArgument::REQUIRED, 'The Packagist package to install (e.g. mautic/example-plugin)');
        $this->addOption('dry-run', null, null, 'Simulate the installation of the package. Doesn\'t actually install it.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        $dryRun      = true === $input->getOption('dry-run') ? true : false;

        try {
            $package = $this->packageModel->getPackageDetail($packageName);
        } catch (ApiException $e) {
            if (404 === $e->getCode()) {
                throw new \InvalidArgumentException('Given package '.$packageName.' does not exist in Packagist. Please check the name for typos.');
            } else {
                throw new \Exception('Error while trying to get package details: '.$e->getMessage());
            }
        }

        if (empty($package->packageBase->type) || 'mautic-plugin' !== $package->packageBase->type) {
            throw new \Exception('Package type is not mautic-plugin. Cannot install this plugin.');
        }

        if ($dryRun) {
            $output->writeLn('Note: dry-running this installation!');
        }

        $output->writeln('Installing '.$input->getArgument('package').', this might take a while...');
        $result = $this->composer->install($input->getArgument('package'), $dryRun);

        if (0 !== $result->exitCode) {
            $output->writeln('<error>Error while installing this plugin.</error>');

            if ($result->output) {
                $output->writeln($result->output);
            } else {
                // If the output is empty then tell the user where to find more details.
                $output->writeln('Check the logs for more details or run again with the -vvv parameter.');
            }

            return $result->exitCode;
        }

        $output->writeln('All done! '.$input->getArgument('package').' has successfully been installed.');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Installs a plugin that is available at Packagist.org';
}
