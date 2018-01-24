<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
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
            ->setName('mautic:segments:update')
            ->setAliases(['mautic:segments:rebuild'])
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
        $translator = $container->get('translator');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $container->get('mautic.lead.model.list');

        $id    = $input->getOption('list-id');
        $batch = $input->getOption('batch-limit');
        $max   = $input->getOption('max-contacts');

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        if ($id) {
            $list = $listModel->getEntity($id);
            if ($list !== null) {
                $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $id]).'</info>');
                $processed = $listModel->updateLeadList($list, $batch, $max, $output);
                $output->writeln(
                    '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'
                );
            } else {
                $output->writeln('<error>'.$translator->trans('mautic.lead.list.rebuild.not_found', ['%id%' => $id]).'</error>');
            }
        } else {
            $lists = $listModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (($l = $lists->next()) !== false) {
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                $output->writeln('<info>'.$translator->trans('mautic.lead.list.rebuild.rebuilding', ['%id%' => $l->getId()]).'</info>');

                $processed = $listModel->rebuildListLeads($l, $batch, $max, $output);
                $output->writeln(
                    '<comment>'.$translator->trans('mautic.lead.list.rebuild.leads_affected', ['%leads%' => $processed]).'</comment>'."\n"
                );

                unset($l);
            }

            unset($lists);
        }

        $this->completeRun();

        return 0;
    }
}
