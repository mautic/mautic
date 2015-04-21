<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadCampaignsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:update')
            ->setAliases(array(
                'mautic:update:campaigns',
                'mautic:rebuild:campaigns',
                'mautic:campaigns:rebuild',
            ))
            ->setDescription('Rebuild campaigns based on lead lists.')
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of leads to process per round. Defaults to 1000.', 1000)
            ->addOption('--max-leads', '-m', InputOption::VALUE_OPTIONAL, 'Set max number of leads to process per campaign for this script execution. Defaults to all.', false)
            ->addOption('--campaign-id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to rebuild. Defaults to all.', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $factory    = $container->get('mautic.factory');
        $translator = $factory->getTranslator();
        $em         = $factory->getEntityManager();

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel = $factory->getModel('campaign');

        $id    = $input->getOption('campaign-id');
        $batch = $input->getOption('batch-limit');
        $max   = $input->getOption('max-leads');

        if ($id) {
            $campaign = $campaignModel->getEntity($id);
            if ($campaign !== null) {
                $output->writeln('<info>' . $translator->trans('mautic.campaign.rebuild.rebuilding', array('%id%' => $id)) . '</info>');
                $processed = $campaignModel->rebuildCampaignLeads($campaign, $batch, $max, $output);
                $output->writeln('<info>' . $translator->trans('mautic.campaign.rebuild.leads_affected', array('%leads%' => $processed)) . '</info>' . "\n");
            } else {
                $output->writeln('<error>' . $translator->trans('mautic.campaign.rebuild.not_found', array('%id%' => $id)) . '</error>');
            }
        } else {
            $campaigns = $campaignModel->getEntities(
                array(
                    'iterator_mode' => true
                )
            );

            while (($c = $campaigns->next()) !== false) {
                if ($c[0]->isPublished()) {
                    $output->writeln('<info>'.$translator->trans('mautic.campaign.rebuild.rebuilding', array('%id%' => $c[0]->getId())).'</info>');

                    $processed = $campaignModel->rebuildCampaignLeads($c[0], $batch, $max, $output);
                    $output->writeln(
                        '<info>'.$translator->trans('mautic.campaign.rebuild.leads_affected', array('%leads%' => $processed)).'</info>'."\n"
                    );
                }

                $em->detach($c[0]);
                unset($c);
            }

            unset($campaigns);
        }

        return 0;
    }
}