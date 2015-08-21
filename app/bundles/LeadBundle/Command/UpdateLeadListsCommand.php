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
            ->addOption('--batch-limit', '-b', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 300.', 300)
            ->addOption('--max-leads', '-m', InputOption::VALUE_OPTIONAL, 'Set max number of leads to process per list for this script execution. Defaults to all.', false)
            ->addOption('--list-id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to rebuild. Defaults to all.', false)
            ->addOption('--force', '-f', InputOption::VALUE_NONE, 'Force execution even if another process is assumed running.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $factory    = $container->get('mautic.factory');
        $translator = $factory->getTranslator();

        // Set SQL logging to null or else will hit memory limits in dev for sure
        $factory->getEntityManager()->getConnection()->getConfiguration()->setSQLLogger(null);

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $factory->getModel('lead.list');

        $id    = $input->getOption('list-id');
        $batch = $input->getOption('batch-limit');
        $max   = $input->getOption('max-leads');
        $force = $input->getOption('force');

        // Prevent script overlap
        $checkFile      = $checkFile = $container->getParameter('kernel.cache_dir').'/../script_executions.json';
        $command        = 'mautic:leadlist:update';
        $key            = ($id) ? $id : 'all';
        $executionTimes = array();

        if (file_exists($checkFile)) {
            // Get the time in the file
            $executionTimes = json_decode(file_get_contents($checkFile), true);
            if (!is_array($executionTimes)) {
                $executionTimes = array();
            }

            if ($force || empty($executionTimes['in_progress'][$command][$key])) {
                // Just started
                $executionTimes['in_progress'][$command][$key] = time();
            } else {
                // In progress
                $check = $executionTimes['in_progress'][$command][$key];

                if ($check + 1800 <= time()) {
                    // Has been 30 minutes so override
                    $executionTimes['in_progress'][$command][$key] = time();
                } else {
                    $output->writeln('<error>Script in progress. Use -f or --force to force execution.</error>');

                    return 0;
                }
            }
        } else {
            // Just started
            $executionTimes['in_progress'][$command][$key] = time();
        }

        if ($id) {
            $list = $listModel->getEntity($id);
            if ($list !== null) {
                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $id)) . '</info>');
                $processed = $listModel->rebuildListLeads($list, $batch, $max, $output);
                $output->writeln('<comment>' . $translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)) . '</comment>');
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
                // Get first item; using reset as the key will be the ID and not 0
                $l = reset($l);

                $output->writeln('<info>' . $translator->trans('mautic.lead.list.rebuild.rebuilding', array('%id%' => $l->getId())) . '</info>');

                $processed = $listModel->rebuildListLeads($l, $batch, $max, $output);
                $output->writeln('<comment>' . $translator->trans('mautic.lead.list.rebuild.leads_affected', array('%leads%' => $processed)) . '</comment>'."\n");

                unset($l);
            }

            unset($lists);
        }

        unset($executionTimes['in_progress'][$command][$key]);
        file_put_contents($checkFile, json_encode($executionTimes));

        return 0;
    }
}