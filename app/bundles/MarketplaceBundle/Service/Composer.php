<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

/**
 * Provides several helper functions to interact with Composer (composer require, remove, etc.)
 */
class Composer
{
    /**
     * Installs a package using its Packagist name.
     * 
     * @param string $packageName The package name, e.g. mautic/example-plugin
     * @param bool $dryRun Whether to dry-run the installation. Comes in handy during automated tests
     *                     and to test whether an installation would succeed or not.
     */
    public function install(string $packageName, bool $dryRun = false): void
    {
        $input = [
            'command' => 'require',
            'packages' => [$packageName]
        ];

        if ($dryRun === true) {
            $input['--dry-run'] = null;
        }

        $this->runCommand($input);
    }

    /**
     * Removes a package using its Packagist name.
     * 
     * @param string $packageName The package name, e.g. mautic/example-plugin
     * @param bool $dryRun Whether to dry-run the removal. Comes in handy during automated tests
     *                     and to test whether an removal would succeed or not.
     */
    public function remove(string $packageName, bool $dryRun = false): void
    {
        $input = [
            'command' => 'remove',
            'packages' => [$packageName]
        ];

        if ($dryRun === true) {
            $input['--dry-run'] = null;
        }

        $this->runCommand($input);
    }

    private function runCommand(array $input): void
    {
        $arrayInput = new ArrayInput(array_merge(
            $input, [ 
                '--no-interaction',
                '--working-dir' => MAUTIC_ROOT_DIR
        ]));

        $application = new Application();
        // We don't want our script to stop after running a Composer command
        $application->setAutoExit(false);

        // TODO capture output and return it with this function
        $application->run($arrayInput);
    }
}