<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class InstallDataCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('mautic:install:data');
        $this->addOption('--force', InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options    = $input->getOptions();
        $force      = (!empty($options['force'])) ? true : false;
        $translator = $this->getContainer()->get('translator');

        if (!$force) {
            $translator->setLocale($this->getContainer()->getParameter('mautic.locale'));

            $dialog  = $this->getHelperSet()->get('dialog');
            $confirm = $dialog->select(
                $output,
                $translator->trans('mautic.core.command.install_data_confirm'),
                array(
                    $translator->trans('mautic.core.form.no'),
                    $translator->trans('mautic.core.form.yes'),
                ),
                0
            );

            if (!$confirm) {
                return 0;
            }
        }

        $env =  (!empty($options['env'])) ? $options['env'] : 'dev';

        $verbosity = $output->getVerbosity();
        $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        //due to foreign restraint and truncate issues with doctrine, the whole schema must be dropped and recreated
        $command = $this->getApplication()->find('doctrine:schema:drop');
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:drop',
            '--force' => true,
            '--env'   => $env,
            '--quiet'  => true
        ));
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            return $returnCode;
        }

        //recreate the database
        $command = $this->getApplication()->find('doctrine:schema:create');
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:create',
            '--env'   => $env,
            '--quiet'  => true
        ));
        $returnCode = $command->run($input, $output);
        if ($returnCode !== 0) {
            return $returnCode;
        }

        //now populate the tables with fixture
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $args = array(
            '--append' => true,
            'command'  => 'doctrine:fixtures:load',
            '--env'    => $env,
            '--quiet'  => true
        );

        $fixtures = $this->getMauticFixtures();
        foreach ($fixtures as $fixture) {
            $args['--fixtures'][] = $fixture;
        }
        $input = new ArrayInput($args);
        $returnCode = $command->run($input, $output);
        if ($returnCode !== 0) {
            return $returnCode;
        }

        $output->setVerbosity($verbosity);
        if (!isset($args['quiet'])) {
            $output->writeln(
                $translator->trans('mautic.core.command.install_data_success')
            );
        }
        return 0;
    }

    /**
     * Returns Mautic fixtures
     *
     * @param bool $returnClassNames
     * @return array
     */
    public function getMauticFixtures($returnClassNames = false)
    {
        $fixtures = array();
        $mauticBundles = $this->getContainer()->getParameter('mautic.bundles');
        foreach ($mauticBundles as $bundle) {
            //parse the namespace into a filepath
            $fixturesDir    = $bundle['directory'] . '/DataFixtures/ORM';

            if (file_exists($fixturesDir)) {
                if ($returnClassNames) {
                    //get files within the directory
                    $finder = new Finder();
                    $finder->files()->in($fixturesDir)->name('*.php');
                    foreach ($finder as $file) {
                        //add the file to be loaded
                        $class      = str_replace(".php", "", $file->getFilename());
                        $fixtures[] = 'Mautic\\' . $bundle['bundle'] . '\\DataFixtures\\ORM\\' . $class;
                    }
                } else {
                    $fixtures[] = $fixturesDir;
                }
            }
        }
        return $fixtures;
    }
}