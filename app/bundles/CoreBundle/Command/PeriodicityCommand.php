<?php

/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PeriodicityCommand extends ModeratedCommand
{

    protected function configure()
    {
        $this->setName('mautic:periodicity:update')
            ->setDescription('Run all periodicity event')
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 300.', 300)
            ->
        // ->addOption(
        // '--max-leads',
        // '-m',
        // InputOption::VALUE_OPTIONAL,
        // 'Send feeds',
        // false
        // )
        addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $factory = $container->get('mautic.factory');
        $translator = $factory->getTranslator();
        $em = $factory->getEntityManager();
        $output->writeln('<info>Execute periodicity event </info>');
        $periodicityModel = $factory->getModel('core.periodicity');
        $periodicitys = $periodicityModel->getRepository()->getEntities();

        /**@var \Mautic\CoreBundle\Entity\Periodicity $p */

        foreach ($periodicitys as $p) {
            if ($p->getNextShoot() > new \DateTime()) {
                continue;
            }



        }
        $this->completeRun();

        return 0;
    }
}