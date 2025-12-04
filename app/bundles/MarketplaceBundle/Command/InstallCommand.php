<?php

namespace Mautic\MarketplaceBundle\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: InstallCommand::NAME,
    description: 'Installs a plugin that is available at Packagist.org'
)]
class InstallCommand
{
    public const NAME = 'mautic:marketplace:install';

    public function __construct(
        private ComposerHelper $composer,
        private PackageModel $packageModel,
    ) {
    }

    public function __invoke(#[\Symfony\Component\Console\Attribute\Argument(name: 'package', description: 'The Packagist package to install (e.g. mautic/example-plugin)')]
        string $package, #[\Symfony\Component\Console\Attribute\Option(name: 'dry-run', description: 'Simulate the installation of the package. Doesn\'t actually install it.')]
        $dryRun, OutputInterface $output): int
    {
        $packageName = (string) $package;
        $dryRun      = (bool) $dryRun;

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

        $output->writeln('Installing '.$packageName.', this might take a while...');
        $result = $this->composer->install($packageName, $dryRun);

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

        $output->writeln('All done! '.$package.' has successfully been installed.');

        return Command::SUCCESS;
    }
}
