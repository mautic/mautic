<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadListsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:leadlists:update')
            ->setAliases(array(
                'mautic:lists:update',
                'mautic:update:leadlists',
                'mautic:update:lists',
                'mautic:rebuild:leadlists',
                'mautic:leadlists:rebuild',
                'mautic:lists:rebuild',
                'mautic:rebuild:lists',
            ))
            ->setDescription('Update leads in smart lists based on new lead data.')
            ->addOption('--batch-limit', null, InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 1000.', 1000)
            ->addOption('--max-leads', null, InputOption::VALUE_OPTIONAL, 'Set max number of leads to process for this script execution. Defaults to all.', false);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();

        $factory = $container->get('mautic.factory');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $factory->getModel('lead.list');

        $lists     = $listModel->getEntities(array(
            'iterator_mode' => true
        ));

        $batch = $input->getOption('batch-limit');
        $max   = $input->getOption('max-leads');

        while (($l = $lists->next()) !== false) {
            $l = reset($l);
            $listModel->rebuildListLeads($l, $batch, $max);

            unset($l);
        }

        unset($lists);

        return 0;
    }
}