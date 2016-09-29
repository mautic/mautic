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
        $fields       = array_merge(FieldModel::$coreFields, FieldModel::$coreCompanyFields);
        $translator   = $this->container->get('translator');
        $indexesToAdd = [];

        /** @var ColumnSchemaHelper $leadsSchema */
        $leadsSchema = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('column', 'leads');
        /** @var ColumnSchemaHelper $companiesSchema */
        $companiesSchema = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('column', 'companies');

        $order = 1;
        foreach ($fields as $alias => $field) {
            $type = isset($field['type']) ? $field['type'] : 'text';

            $entity = new LeadField();
            $entity->setLabel($translator->trans('mautic.lead.field.'.$alias, [], 'fixtures'));
            $entity->setGroup(isset($field['group']) ? $field['group'] : 'core');
            $entity->setOrder($order);
            $entity->setAlias($alias);
            $entity->setType($type);
            $entity->setObject($field['object']);
            $entity->setIsUniqueIdentifer(!empty($field['unique']));
            $entity->setProperties(isset($field['properties']) ? $field['properties'] : []);
            $entity->setIsFixed(!empty($field['fixed']));
            $entity->setIsListable(!empty($field['listable']));
            $entity->setIsShortVisible(!empty($field['short']));

            $manager->persist($entity);
            $manager->flush();

            if(isset ($field['object']) and $field['object'] == 'company')
            {
                //add the column to the companies table
                $companiesSchema->addColumn(
                    FieldModel::getSchemaDefinition($alias, $type, $entity->getIsUniqueIdentifier())
                );
                $indexesToAdd[$field['object']][] = $alias;
            } else {
                //add the column to the leads table
                $leadsSchema->addColumn(
                    FieldModel::getSchemaDefinition($alias, $type, $entity->getIsUniqueIdentifier())
                );
                $indexesToAdd['lead'][] = $alias;
            }

            $this->addReference('leadfield-'.$alias, $entity);
            $order++;
        }

        $leadsSchema->executeChanges();

        /** @var IndexSchemaHelper $indexHelper */
        $indexHelper = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('index', 'leads');
        /** @var IndexSchemaHelper $indexHelper */
        $companyIndexHelper = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('index', 'companies');

        foreach ($indexesToAdd as $object => $indexes) {
            foreach ($indexes as $name) {
                $type = (isset($fields[$name]['type'])) ? $fields[$name]['type'] : 'text';
                if ($object == 'company'){
                    if ('textarea' != $type) {
                        $companyIndexHelper->addIndex([$name], MAUTIC_TABLE_PREFIX.$name.'_search');
                    } else {
                        $indexHelper->addIndex([$name], MAUTIC_TABLE_PREFIX.$name.'_search');
                    }
                }

            }
            }
        // Add an attribution index
        $indexHelper->addIndex(['attribution', 'attribution_date'], MAUTIC_TABLE_PREFIX . '_contact_attribution');
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
