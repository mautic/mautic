<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Update\Step;

use Mautic\CoreBundle\Exception\UpdateFailedException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class UpdateSchemaStep implements StepInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(TranslatorInterface $translator, ContainerInterface $container)
    {
        $this->translator = $translator;
        $this->kernel     = $container->get('kernel');
    }

    public function getOrder(): int
    {
        return 40;
    }

    public function shouldExecuteInFinalStage(): bool
    {
        return true;
    }

    /**
     * @throws UpdateFailedException
     */
    public function execute(ProgressBar $progressBar, InputInterface $input, OutputInterface $output): void
    {
        // Migrate the database to the current version
        $progressBar->setMessage($this->translator->trans('mautic.core.update.migrating.database.schema'));
        $progressBar->advance();

        $migrationApplication = new Application($this->kernel);
        $migrationApplication->setAutoExit(false);

        $migrationCommandArgs = new ArgvInput(['console', 'doctrine:migrations:migrate', '--quiet', '--no-interaction']);
        $migrationCommandArgs->setInteractive(false);

        $migrateExitCode = $migrationApplication->run($migrationCommandArgs, new NullOutput());

        // Output the error (if exists) from the migrate command after we've finished the progress bar
        if (0 !== $migrateExitCode) {
            throw new UpdateFailedException($this->translator->trans('mautic.core.update.error_performing_migration'));
        }
    }
}
