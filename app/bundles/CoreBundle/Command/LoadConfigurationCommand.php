<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\UserBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\RuntimeException;

/**
 * CLI Command to perform the initial installation of the application
 */
class LoadConfigurationCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:load:configuration')
            ->setDescription('Pre-configures Mautic for installation with a pre-generated data set')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command is used to pre-configure Mautic using a pre-generated data set

<info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->getContainer()->get('mautic.configurator');
        $kernelRoot   = $this->getContainer()->getParameter('kernel.root_dir');

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale('en_US');

        // Load up the pre-loaded data
        $data = unserialize(file_get_contents($kernelRoot . '/config/install_data.txt'));

        // Extract out the user data that won't be part of the configuration
        unset($data['username']);
        unset($data['password']);
        unset($data['email']);

        // Merge in the rest of our configuration data
        $data = array_merge($data, array(
           'secret' => hash('sha1', uniqid(mt_rand())),
           'default_pagelimit' => 10
        ));

        $configurator->mergeParameters($data);

        try {
            $configurator->write();
        } catch (RuntimeException $exception) {
            $output->writeln($translator->trans('mautic.core.command.install_application_could_not_write_config', array('%message%' => $exception->getMessage())));

            return 1;
        }

        $output->writeln(
            $translator->trans('mautic.core.command.install_application_configuration_loaded')
        );

        return 0;
    }
}
