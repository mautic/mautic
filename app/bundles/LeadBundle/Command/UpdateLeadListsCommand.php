<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadListsCommand extends ModeratedCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:contactsegments:update')
            ->setAliases(
                array(
                    'mautic:segments:update',
                    'mautic:update:contactsegments',
                    'mautic:update:segments',
                    'mautic:rebuild:contactsegments',
                    'mautic:contactsegments:rebuild',
                    'mautic:segments:rebuild',
                    'mautic:rebuild:segments',

                    // Following aliases: BC support; @deprecated 1.1.4; to be removed in 2.0
                    'mautic:lists:update',
                    'mautic:update:leadlists',
                    'mautic:update:lists',
                    'mautic:rebuild:leadlists',
                    'mautic:leadlists:rebuild',
                    'mautic:lists:rebuild',
                    'mautic:rebuild:lists',
                    'mautic:leadlists:update'
                )
            )
            ->setDescription('Update contacts in smart segments based on new contact data.')
            ->addOption('--batch-limit', '-b', InputOption::VALUE_OPTIONAL, 'Set batch size of contacts to process per round. Defaults to 300.', 300)
            ->addOption(
                '--max-contacts',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of contacts to process per segment for this script execution. Defaults to all.',
                false
            )
            ->addOption('--list-id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to rebuild. Defaults to all.', false);

        parent::configure();
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
        $max   = $input->getOption('max-contacts');

        if (!$this->checkRunStatus($input, $output, ($id) ? $id : 'all')) {

            return 0;
        }

        if ($id) {
            $list = $listModel->getEntity($id);
            if ($list !== null) {
                $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $id)).'</info>');
                $processed = $listModel->rebuildListLeads($list, $batch, $max, $output);
                $output->writeln(
                    '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)).'</comment>'
                );
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.lead.list.rebuild.not_found', array('%id%' => $id)).'</error>');
            }
        } else {
            $lists = $listModel->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($l = $lists->next()) !== false) {
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $l->getId())).'</info>');

                $processed = $listModel->rebuildListLeads($l, $batch, $max, $output);
                $output->writeln(
                    '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)).'</comment>'."\n"
                );

                unset($l);
            }

            unset($lists);
        }

        $this->completeRun();

        return 0;
    }
}