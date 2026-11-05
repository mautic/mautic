<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Command;

use Mautic\CoreBundle\Helper\ComposerHelper;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Model\PackageModel;
use Mautic\MarketplaceBundle\Service\ResourceInstallerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: RemoveCommand::NAME,
    description: 'Removes a plugin or resource that is currently installed'
)]
final class RemoveCommand extends Command
{
    public const NAME = 'mautic:marketplace:remove';

    public function __construct(
        private readonly ComposerHelper $composer,
        private readonly LoggerInterface $logger,
        private readonly PackageModel $packageModel,
        private readonly ResourceInstallerInterface $resourceInstaller,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::REQUIRED, 'The Packagist package to remove (e.g. mautic/example-plugin)');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        $output->writeln('Removing '.$packageName.', this might take a while...');

        try {
            $package = $this->packageModel->getPackageDetail($packageName);
        } catch (ApiException $e) {
            if (404 === $e->getCode()) {
                $output->writeln('<error>Package '.$packageName.' not found.</error>');

                return Command::FAILURE;
            }
            throw new \Exception('Error while trying to get package details: '.$e->getMessage(), $e->getCode(), $e);
        }

        $type = $package->packageBase->type ?? '';

        if ('mautic-resource' === $type) {
            return $this->removeResource($packageName, $output);
        }

        // Just checking the package type so that the user doesn't accidentally remove a core package
        if (!in_array($packageName, $this->composer->getMauticPluginPackages())) {
            $output->writeln('This package cannot be removed, it must be of type mautic-plugin');

            return Command::FAILURE;
        }

        $removeResult = $this->composer->remove($packageName);

        if (0 !== $removeResult->exitCode) {
            $message = 'Error while removing plugin through Composer: '.$removeResult->output;
            $this->logger->error($message);
            $output->writeLn($message);

            return Command::FAILURE;
        }

        $output->writeln($packageName.' has successfully been removed.');

        return Command::SUCCESS;
    }

    private function removeResource(string $packageName, OutputInterface $output): int
    {
        if (!$this->resourceInstaller->isInstalled($packageName)) {
            $output->writeln('<error>'.$packageName.' is not currently installed.</error>');

            return Command::FAILURE;
        }

        try {
            $this->resourceInstaller->uninstall($packageName);
        } catch (\Exception $e) {
            $output->writeln('<error>Error while removing resource: '.$e->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln($packageName.' has successfully been removed.');

        return Command::SUCCESS;
    }
}
