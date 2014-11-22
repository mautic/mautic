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
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLeadListsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('mautic:leadlists:update')
            ->setDescription('Update leads in smart lists based on new lead data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();

        $factory = $container->get('mautic.factory');

        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel = $factory->getModel('lead.list');

        $lists = $listModel->getEntities();

        foreach ($lists as $l) {
            $listModel->regenerateListLeads($l);
        }

        return 0;
    }
}