<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
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
class InstallApplicationCommand extends ContainerAwareCommand
{

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:install:application');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $input->getOptions();

        /** @var \Mautic\InstallBundle\Configurator\Configurator $configurator */
        $configurator = $this->getContainer()->get('mautic.configurator');
        $kernelRoot   = $this->getContainer()->getParameter('kernel.root_dir');

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');
        $translator->setLocale('en_US');

        // Load up the pre-loaded data
        $data = unserialize(file_get_contents($kernelRoot . '/config/install_data.txt'));

        // Extract out the user data
        $userData = array(
            'name'     => $data['username'],
            'password' => $data['password'],
            'email'    => $data['email']
        );

        $env = $this->getContainer()->getParameter('kernel.environment');

        // Create the database
        $command = $this->getApplication()->find('doctrine:schema:create');
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:create',
            '--env'   => $env,
            '--quiet'  => true
        ));
        $returnCode = $command->run($input, $output);

        if ($returnCode !== 0) {
            $output->writeln($translator->trans('mautic.core.command.install_application_could_not_create_database'));

            return $returnCode;
        }

        // Now populate the tables with fixtures
        $command = $this->getApplication()->find('doctrine:fixtures:load');
        $args = array(
            '--append'   => true,
            'command'    => 'doctrine:fixtures:load',
            '--env'      => $env,
            '--quiet'    => true,
            '--fixtures' => $kernelRoot . '/bundles/InstallBundle/InstallFixtures/ORM'
        );

        $input = new ArrayInput($args);
        $returnCode = $command->run($input, $output);
        if ($returnCode !== 0) {
            $output->writeln($translator->trans('mautic.core.command.install_application_could_not_load_fixtures'));

            return $returnCode;
        }

        try {
            /** @var \Doctrine\ORM\EntityManager $entityManager */
            $entityManager = $this->getContainer()->get('doctrine')->getManager();

            // Now we create the user
            $user = new User();

            /** @var \Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder */
            $encoder = $this->getContainer()->get('security.encoder_factory')->getEncoder($user);

            /** @var \Mautic\UserBundle\Model\RoleModel $model */
            $model = $this->getContainer()->get('mautic.factory')->getModel('user.role');

            $user->setFirstName($translator->trans('mautic.user.user.admin.name', array(), 'fixtures'));
            $user->setLastName($translator->trans('mautic.user.user.admin.name', array(), 'fixtures'));
            $user->setUsername($userData['name']);
            $user->setEmail($userData['email']);
            $user->setPassword($encoder->encodePassword($userData['password'], $user->getSalt()));
            $user->setRole($model->getEntity(1));
            $entityManager->persist($user);
            $entityManager->flush();
        } catch (\Exception $exception) {
            $output->writeln($translator->trans('mautic.core.command.install_application_could_not_create_user', array('%message%' => $exception->getMessage())));

            return 0;
        }

        $output->writeln(
            $translator->trans('mautic.core.command.install_application_success')
        );

        return 0;
    }
}
