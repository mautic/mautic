<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Mautic\LeadBundle\Entity\LeadField;

/**
 * Class LeadFieldData
 */
class LeadFieldData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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

        $indexesToAdd = [];
        $textfields = [
            'title',
            'firstname',
            'lastname',
            'company',
            'position',
            'email',
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
            'googleplus',
            'skype',
            'linkedin',
            'instagram',
            'foursquare',
            'attribution',
            'attribution_date'
        ];

        /** @var ColumnSchemaHelper $leadsSchema */
        $leadsSchema = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('column', 'leads');

        foreach ($textfields as $key => $name) {
            $entity = new LeadField();
            $entity->setLabel($translator->trans('mautic.lead.field.'.$name, [], 'fixtures'));
            if (in_array($name, ['title', 'company', 'city', 'zipcode'])) {
                $type = 'lookup';
            } elseif ($name == 'country') {
                $type = 'country';
            } elseif ($name == 'state') {
                $type = 'region';
            } elseif (in_array($name, ['phone', 'mobile', 'fax'])) {
                $type = 'tel';
            } elseif ($name == 'website') {
                $type = 'url';
            } elseif ($name == 'email') {
                $type = 'email';
                $entity->setIsUniqueIdentifer(true);
            } elseif ($name == 'attribution_date') {
                $type = 'datetime';
            } elseif ($name == 'attribution') {
                $type = 'number';
            } else {
                $type = 'text';
            }

            if ($name == 'title') {
                $entity->setProperties(["list" => "|Mr|Mrs|Miss"]);
            }
            $entity->setType($type);
            $fixed = in_array(
                $name,
                [
                    'attribution',
                    'attribution_date',
                    'title',
                    'firstname',
                    'lastname',
                    'position',
                    'company',
                    'email',
                    'phone',
                    'mobile',
                    'address1',
                    'address2',
                    'country',
                    'city',
                    'state',
                    'zipcode'
                ]
            ) ? true : false;
            $entity->setIsFixed($fixed);

            $entity->setOrder(($key + 1));
            $entity->setAlias($name);
            $listable = in_array(
                $name,
                [
                    'attribution',
                    'attribution_date',
                    'address1',
                    'address2',
                    'fax',
                    'phone',
                    'mobile',
                    'fax',
                    'twitter',
                    'facebook',
                    'googleplus',
                    'skype',
                    'linkedin',
                    'foursquare',
                    'instagram',
                    'website'
                ]
            ) ? false : true;
            $entity->setIsListable($listable);

            $shortVisible = in_array($name, ['firstname', 'lastname', 'email']) ? true : false;
            $entity->setIsShortVisible($shortVisible);

            $group = (in_array($name, ['twitter', 'facebook', 'googleplus', 'skype', 'linkedin', 'instagram', 'foursquare'])) ? 'social' : 'core';
            $entity->setGroup($group);

            $manager->persist($entity);
            $manager->flush();

            //add the column to the leads table
            $leadsSchema->addColumn(
                FieldModel::getSchemaDefinition($name, $type, $entity->getIsUniqueIdentifier())
            );

            $indexesToAdd[] = $name;

            $this->addReference('leadfield-'.$name, $entity);
        }

        $leadsSchema->executeChanges();

        /** @var IndexSchemaHelper $indexHelper */
        $indexHelper = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('index', 'leads');

        foreach ($indexesToAdd as $name) {
            $indexHelper->addIndex([$name], MAUTIC_TABLE_PREFIX.$name.'_search', ['where' => "({$name}(767))"] );
        }

        // Add an attribution index
        $indexHelper->addIndex(['attribution', 'attribution_date'], MAUTIC_TABLE_PREFIX.'_contact_attribution');

        $indexHelper->executeChanges();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
