<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

/**
 * CLI Command to install Mautic sample data.
 */
class InstallDataCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:install:data')
            ->setDescription('Installs Mautic with sample data')
            ->setDefinition([
                new InputOption(
                    'force', null, InputOption::VALUE_NONE, 'Bypasses the verification check.'
                ),
            ])
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command re-installs Mautic with sample data.

<info>php %command.full_name%</info>

You can optionally specify to bypass the verification check with the --force option:

<info>php %command.full_name% --force</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options    = $input->getOptions();
        $force      = $options['force'];
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.factory')->getParameter('locale'));

        if (!$force) {
            $dialog  = $this->getHelperSet()->get('dialog');
            $confirm = $dialog->select(
                $output,
                $translator->trans('mautic.core.command.install_data_confirm'),
                [
                    $translator->trans('mautic.core.form.no'),
                    $translator->trans('mautic.core.form.yes'),
                ],
                0
            );

            if (!$confirm) {
                return 0;
            }
        }

        $env = $options['env'];

        // TODO - This should respect the --quiet flag
        $verbosity = $output->getVerbosity();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        //due to foreign restraint and truncate issues with doctrine, the whole schema must be dropped and recreated
        $command = $this->getApplication()->find('doctrine:schema:drop');
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            return $returnCode;
        }

        //recreate the database
        $command = $this->getApplication()->find('doctrine:schema:create');
        $input   = new ArrayInput([
            'command' => 'doctrine:schema:create',
            '--env'   => $env,
            '--quiet' => true,
        ]);
        $returnCode = $command->run($input, $output);
        if ($returnCode !== 0) {
            return $returnCode;
        }

        //now populate the tables with fixture
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $args    = [
            'command'  => 'doctrine:fixtures:load',
            '--append' => true,
            '--env'    => $env,
            '--quiet'  => true,
        ];

        $fixtures = $this->getMauticFixtures();
        foreach ($fixtures as $fixture) {
            $args['--fixtures'][] = $fixture;
        }
        $input      = new ArrayInput($args);
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            return $returnCode;
        }

        $output->setVerbosity($verbosity);
        $output->writeln(
            $translator->trans('mautic.core.command.install_data_success')
        );

        return 0;
    }

    /**
     * Returns Mautic fixtures.
     *
     * @param bool $returnClassNames
     *
     * @return array
     */
    public function getMauticFixtures($returnClassNames = false)
    {
        $fixtures      = [];
        $mauticBundles = $this->getContainer()->getParameter('mautic.bundles');
        foreach ($mauticBundles as $bundle) {
            $fixturesDir = $bundle['directory'].'/DataFixtures/ORM';

            if (file_exists($fixturesDir)) {
                $classPrefix = 'Mautic\\'.$bundle['bundle'].'\\DataFixtures\\ORM\\';
                $this->populateFixturesFromDirectory($fixturesDir, $fixtures, $classPrefix, $returnClassNames);
            }

            $testFixturesDir = $bundle['directory'].'/Tests/DataFixtures/ORM';

            if (MAUTIC_TEST_ENV && file_exists($testFixturesDir)) {
                $classPrefix = 'Mautic\\'.$bundle['bundle'].'\\Tests\\DataFixtures\\ORM\\';
                $this->populateFixturesFromDirectory($testFixturesDir, $fixtures, $classPrefix, $returnClassNames);
            }
        }

        return $fixtures;
    }

    /**
     * @param string $fixturesDir
     * @param array  $fixtures
     * @param string $classPrefix
     * @param bool   $returnClassNames
     */
    private function populateFixturesFromDirectory($fixturesDir, array &$fixtures, $classPrefix = null, $returnClassNames = false)
    {
        if ($returnClassNames) {
            //get files within the directory
            $finder = new Finder();
            $finder->files()->in($fixturesDir)->name('*.php');
            foreach ($finder as $file) {
                //add the file to be loaded
                $class      = str_replace('.php', '', $file->getFilename());
                $fixtures[] = $classPrefix.$class;
            }
        } else {
            $fixtures[] = $fixturesDir;
        }
    }
}
