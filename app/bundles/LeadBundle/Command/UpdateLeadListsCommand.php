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
            ->setDescription('Update leads in smart lists based on new lead data.')
            ->addOption('--limit', null, InputOption::VALUE_OPTIONAL, 'Max number of leads to process per command. Defaults to 1000.', 1000);
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

        $limit = $input->getOption('limit');

        while (($l = $lists->next()) !== false) {
            $l = reset($l);
            $listModel->regenerateListLeads($l, $limit);

            unset($l);
        }

        unset($lists);

        return 0;
    }
}