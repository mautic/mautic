<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: InstallCommand::NAME,
    description: 'Installs a plugin or resource from the Marketplace'
)]
final class InstallCommand extends Command
{
    public const NAME = 'mautic:marketplace:install';

    public function __construct(
        private readonly ComposerHelper $composer,
        private readonly PackageModel $packageModel,
        private readonly ResourceInstallerInterface $resourceInstaller,
        private readonly UserModel $userModel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::REQUIRED, 'The Packagist package to install (e.g. mautic/example-plugin)');
        $this->addOption('dry-run', null, null, 'Simulate the installation of the package. Doesn\'t actually install it.');
        $this->addOption('user-id', null, InputOption::VALUE_REQUIRED, 'ID of the admin user who will own the installed resource. Required for mautic-resource packages when not run interactively.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        $dryRun      = true === $input->getOption('dry-run');

        try {
            $package = $this->packageModel->getPackageDetail($packageName);
        } catch (ApiException $e) {
            if (404 === $e->getCode()) {
                throw new \InvalidArgumentException('Given package '.$packageName.' does not exist in Packagist. Please check the name for typos.', $e->getCode(), $e);
            }
            throw new \Exception('Error while trying to get package details: '.$e->getMessage(), $e->getCode(), $e);
        }

        $type = $package->packageBase->type ?? '';

        if ('mautic-resource' === $type) {
            return $this->installResource($packageName, $dryRun, $input, $output);
        }

        if (!in_array($type, ['mautic-plugin', 'mautic-theme'], true)) {
            throw new \Exception('Unsupported package type: '.$type);
        }

        if ($dryRun) {
            $output->writeLn('Note: dry-running this installation!');
        }

        $output->writeln('Installing '.$input->getArgument('package').', this might take a while...');
        $result = $this->composer->install($input->getArgument('package'), $dryRun);

        if (0 !== $result->exitCode) {
            $output->writeln(sprintf('<error>Error while installing this %s.</error>', 'mautic-theme' === $type ? 'theme' : 'plugin'));

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

    private function installResource(string $packageName, bool $dryRun, InputInterface $input, OutputInterface $output): int
    {
        if ($dryRun) {
            $output->writeln('Note: dry-run mode. Would install resource '.$packageName);

            return Command::SUCCESS;
        }

        $userId = $this->resolveOwnerUserId($input, $output);

        $output->writeln('Installing resource '.$packageName.', this might take a while...');

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

    private function resolveOwnerUserId(InputInterface $input, OutputInterface $output): int
    {
        $providedUserId = $input->getOption('user-id');
        if (null !== $providedUserId) {
            $userId = (int) $providedUserId;
            $user   = $this->userModel->getEntity($userId);

            if (null === $user) {
                throw new \InvalidArgumentException(sprintf('User with ID %d was not found.', $userId));
            }

            if (!$user->isAdmin()) {
                throw new \InvalidArgumentException(sprintf('User %d is not an admin. Resources must be owned by an admin user.', $userId));
            }

            return $userId;
        }

        $adminUsers = $this->userModel->getRepository()->getAllAdminUsers();
        if ([] === $adminUsers) {
            throw new \RuntimeException('No admin users found. Create one first or pass --user-id.');
        }

        if (!$input->isInteractive()) {
            throw new \InvalidArgumentException('--user-id is required when running non-interactively.');
        }

        $choices = [];
        foreach ($adminUsers as $user) {
            $choices[(int) $user->getId()] = sprintf('%s (%s)', $user->getName(), $user->getEmail());
        }

        $io       = new SymfonyStyle($input, $output);
        $selected = $io->choice('Select the admin user who will own this resource', $choices);

        return (int) array_search($selected, $choices, true);
    }
}
