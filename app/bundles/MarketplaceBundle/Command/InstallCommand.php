<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Service\ResourceInstaller;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: InstallCommand::NAME,
    description: 'Installs a plugin or resource from the Marketplace'
)]
class InstallCommand extends Command
{
    public const NAME = 'mautic:marketplace:install';

    public function __construct(
        private ComposerHelper $composer,
        private PackageModel $packageModel,
        private ResourceInstaller $resourceInstaller,
        private UserHelper $userHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
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

        $type = $package->packageBase->type ?? '';

        if ('mautic-resource' === $type) {
            return $this->installResource($packageName, $dryRun, $output);
        }

        if ('mautic-plugin' !== $type) {
            throw new \Exception('Unsupported package type: '.$type);
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
                $output->writeln('Check the logs for more details or run again with the -vvv parameter.');
            }

            return $result->exitCode;
        }

        $output->writeln('All done! '.$input->getArgument('package').' has successfully been installed.');

        return Command::SUCCESS;
    }

    private function installResource(string $packageName, bool $dryRun, OutputInterface $output): int
    {
        if ($dryRun) {
            $output->writeln('Note: dry-run mode. Would install resource '.$packageName);

            return Command::SUCCESS;
        }

        $output->writeln('Installing resource '.$packageName.', this might take a while...');

        $userId = $this->userHelper->getUser()->getId() ?? 1;
        $result = $this->resourceInstaller->install($packageName, $userId);

        if (!$result['success']) {
            $output->writeln('<error>Error while installing this resource.</error>');
            foreach ($result['errors'] as $error) {
                $output->writeln($error);
            }

            return Command::FAILURE;
        }

        $output->writeln('All done! '.$packageName.' has successfully been installed.');

        return Command::SUCCESS;
    }
}
