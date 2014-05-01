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

class InstallDataCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('mautic:install:data');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->getParameter('mautic.locale'));

        $dialog     = $this->getHelperSet()->get('dialog');
        $confirm    = $dialog->select(
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

        //due to foreign restraint and truncate issues with doctrine, the whole schema must be dropped and recreated
        $command = $this->getApplication()->find('doctrine:schema:drop');
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:drop',
            '--force' => true
        ));
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            return $returnCode;
        }

        //recreate the database
        $command = $this->getApplication()->find('doctrine:schema:create');
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:create'
        ));
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            return $returnCode;
        }

        //now populate the tables with fixture
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $input = new ArrayInput(array(
                '--append' => true,
                'command'  => 'doctrine:fixtures:load'

            )
        );
        $returnCode = $command->run($input, $output);
        if ($returnCode !== 0) {
            return $returnCode;
        }

        $output->writeln(
            $translator->trans('mautic.core.command.install_data_success')
        );
        return 0;
    }
}