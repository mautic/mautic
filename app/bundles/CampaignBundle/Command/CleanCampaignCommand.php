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
            ->setDescription('Clear event log for campaigns that have an especific category.')
            ->addOption(
                '--category-id',
                '-i',
                InputOption::VALUE_REQUIRED,
                'The category ID'
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
        $connection     = $entityManager->getConnection();
        $categoryId     = $input->getOption('category-id');
        $category       = $entityManager->find('MauticCategoryBundle:Category', $categoryId);

        if (!$category) {
            throw new Exception('Category not found.');
        }        

        $campaigns = $entityManager->getRepository('MauticCampaignBundle:Campaign')->findByCategory($category);

        foreach ($campaigns as $campaign) {
            $criteria = ['campaign_id' => $campaign->getId()];

            $connection->delete(MAUTIC_TABLE_PREFIX . 'campaign_lead_event_log', $criteria);
        }
    }
}
