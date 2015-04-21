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
            ->addOption('--batch-limit', '-b', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 1000.', 1000)
            ->addOption('--max-leads', '-m', InputOption::VALUE_OPTIONAL, 'Set max number of leads to process per list for this script execution. Defaults to all.', false)
            ->addOption('--list-id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to rebuild. Defaults to all.', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $factory    = $container->get('mautic.factory');
        $translator = $factory->getTranslator();

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $factory->getModel('lead.list');

        $id    = $input->getOption('list-id');
        $batch = $input->getOption('batch-limit');
        $max   = $input->getOption('max-leads');

        if ($id) {
            $list = $listModel->getEntity($id);
            if ($list !== null) {
                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $id)) . '</info>');
                $processed = $listModel->rebuildListLeads($list, $batch, $max, $output);
                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)) . '</info>');
            } else {
                $output->writeln('<error>' . $translator->trans('mautic.lead.list.rebuild.not_found', array('%id%' => $id)) . '</error>');
            }
        } else {
            $lists = $listModel->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($l = $lists->next()) !== false) {
                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $l[0]->getId())) . '</info>');

                $processed = $listModel->rebuildListLeads($l[0], $batch, $max, $output);
                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)) . '</info>');

                unset($l);
            }

            unset($lists);
        }

        return 0;
    }
}