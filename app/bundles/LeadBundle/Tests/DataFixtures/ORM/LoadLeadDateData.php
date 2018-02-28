<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadLeadDateData.
 */
class LoadLeadDateData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->container->get('doctrine.orm.default_entity_manager')->getRepository(Lead::class);

        $data = [
            [
                'name'         => 'Today',
                'initialTime'  => 'midnight today',
                'dateModifier' => '+10 seconds',
            ],
            [
                'name'         => 'Tomorrow',
                'initialTime'  => 'midnight tomorrow',
                'dateModifier' => '+10 seconds',
            ],
            [
                'name'         => 'Yesterday',
                'initialTime'  => 'midnight today',
                'dateModifier' => '-10 seconds',
            ],
            [
                'name'         => 'Last week',
                'initialTime'  => 'midnight monday last week',
                'dateModifier' => '+2 days',
            ],
            [
                'name'         => 'Next week',
                'initialTime'  => 'midnight monday next week',
                'dateModifier' => '+2 days',
            ],
            [
                'name'         => 'This week',
                'initialTime'  => 'midnight monday this week',
                'dateModifier' => '+2 days',
            ],
            [
                'name'         => 'Last month',
                'initialTime'  => 'midnight first day of last month',
                'dateModifier' => '+2 days',
            ],
            [
                'name'         => 'Next month',
                'initialTime'  => 'midnight first day of next month',
                'dateModifier' => '+2 days',
            ],
            [
                'name'         => 'This month',
                'initialTime'  => 'midnight first day of this month',
                'dateModifier' => '+2 days',
            ],
        ];
        $data = [];

        foreach ($data as $lead) {
            $this->createLead($leadRepository, $lead['name'], $lead['initialTime'], $lead['dateModifier']);
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 6;
    }

    /**
     * @param LeadRepository $leadRepository
     * @param string         $name
     * @param string         $initialTime
     * @param string         $dateModifier
     */
    private function createLead(LeadRepository $leadRepository, $name, $initialTime, $dateModifier)
    {
        $date = new \DateTime($initialTime);
        $date->modify($dateModifier);

        $lead = new Lead();
        $lead->setLastname('Date');
        $lead->setFirstname($name);
        $lead->setDateIdentified($date);

        $leadRepository->saveEntity($lead);

        $alias = strtolower(InputHelper::alphanum($name, false, '-'));

        $this->setReference('lead-date-'.$alias, $lead);
    }
}
