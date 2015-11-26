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

/**
 * Class CleanCampaignCommand
 */
class CleanCampaignCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:clean')
            ->setDescription('Clean event cache for one or all campaigns.')
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Clear event log for a specific campaign. Otherwise, all campaigns will be cleaned.'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container      = $this->getContainer();
        $factory        = $container->get('mautic.factory');
        $entityManager  = $factory->getEntityManager();
        $campaignId     = $input->getOption('campaign-id');
        $criteria       = ['campaign_id' => (int)$campaignId];

        $conn = $entityManager->getConnection();
        $conn->delete(MAUTIC_TABLE_PREFIX . 'campaign_lead_event_log', $criteria);

        return 0;
    }
}
