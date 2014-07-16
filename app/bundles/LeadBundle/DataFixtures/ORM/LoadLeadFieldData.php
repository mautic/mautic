<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class LoadLeadFieldData
 *
 * @package Mautic\LeadBundle\DataFixtures\ORM
 */
class LoadLeadFieldData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $translator = $this->container->get('translator');

        $textfields = array(
            'firstname',
            'lastname',
            'company',
            'email',
            'title',
            'phone',
            'mobile',
            'fax',
            'address1',
            'address2',
            'city',
            'state',
            'zipcode',
            'country',
            'website',
            'twitter',
            'facebook',
            'plus',
            'skype'
        );

        foreach ($textfields as $key => $name) {
            $entity = new LeadField();
            $entity->setLabel($translator->trans('mautic.lead.field.'.$name, array(), 'fixtures'));
            if (in_array($name, array('title', 'company', 'city', 'state', 'zipcode', 'country'))) {
                $type = 'lookup';
            } elseif (in_array($name, array('phone', 'mobile', 'fax'))) {
                $type = 'tel';
            } elseif ($name == 'website') {
                $type = 'url';
            } elseif ($name == 'email') {
                $type = 'email';
            } else {
                $type = 'text';
            }

            if ($name == 'title') {
                $entity->setProperties(array("list" =>"|Mr|Mrs|Miss"));
            }
            $entity->setType($type);
            $fixed = in_array($name, array('firstname', 'lastname', 'company', 'email', 'country', 'city', 'state')) ? true : false;
            $entity->setIsFixed($fixed);
            $entity->setOrder(($key+1));
            $entity->setAlias($name);
            $listable    = in_array($name, array(
                'address1',
                'address2',
                'fax',
                'phone',
                'twitter',
                'facebook',
                'plus',
                'skype',
                'mobile',
                'website'
            )) ? false : true;
            $entity->setIsListable($listable);
            $manager->persist($entity);
            $manager->flush();
            $this->addReference('leadfield-'.$name, $entity);
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }
}