<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadLeadData.
 */
class LoadLeadData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $leadRepo = $this->container->get('mautic.lead.model.lead')->getRepository();
        $today    = new \DateTime();

        $leads = CsvHelper::csv_to_array(__DIR__.'/fakeleaddata.csv');
        foreach ($leads as $count => $l) {
            $key  = $count + 1;
            $lead = new Lead();
            $lead->setDateAdded($today);
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($l['ip'], $this->container->get('mautic.helper.core_parameters')->getParameter('parameters'));
            $this->setReference('ipAddress-'.$key, $ipAddress);
            unset($l['ip']);
            $lead->addIpAddress($ipAddress);
            $lead->setOwner($this->getReference('sales-user'));
            foreach ($l as $col => $val) {
                $lead->addUpdatedField($col, $val);
            }
            $leadRepo->saveEntity($lead);

            $this->setReference('lead-'.$count, $lead);
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }
}
