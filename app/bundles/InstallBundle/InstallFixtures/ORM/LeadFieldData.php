<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Doctrine\Helper\IndexSchemaHelper;
use Mautic\CoreBundle\Exception\SchemaException;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LeadFieldData.
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
        $fieldGroups['lead']    = FieldModel::$coreFields;
        $fieldGroups['company'] = FieldModel::$coreCompanyFields;

        $translator   = $this->container->get('translator');
        $indexesToAdd = [];
        foreach ($fieldGroups as $object => $fields) {
            if ($object == 'company') {
                /** @var ColumnSchemaHelper $schema */
                $schema = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('column', 'companies');
            } else {
                /** @var ColumnSchemaHelper $schema */
                $schema = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('column', 'leads');
            }

            $order = 1;
            foreach ($fields as $alias => $field) {
                $type = isset($field['type']) ? $field['type'] : 'text';

                $entity = new LeadField();
                $entity->setLabel($translator->trans('mautic.lead.field.'.$alias, [], 'fixtures'));
                $entity->setGroup(isset($field['group']) ? $field['group'] : 'core');
                $entity->setOrder($order);
                $entity->setAlias($alias);
                $entity->setIsRequired(isset($field['required']) ? $field['required'] : false);
                $entity->setType($type);
                $entity->setObject($field['object']);
                $entity->setIsUniqueIdentifer(!empty($field['unique']));
                $entity->setProperties(isset($field['properties']) ? $field['properties'] : []);
                $entity->setIsFixed(!empty($field['fixed']));
                $entity->setIsListable(!empty($field['listable']));
                $entity->setIsShortVisible(!empty($field['short']));

                $manager->persist($entity);
                $manager->flush();

                try {
                    $schema->addColumn(
                        FieldModel::getSchemaDefinition($alias, $type, $entity->getIsUniqueIdentifier())
                    );
                } catch (SchemaException $e) {
                    // Schema already has this custom field; likely defined as a property in the entity class itself
                }

                $indexesToAdd[$object][] = $alias;

                $this->addReference('leadfield-'.$alias, $entity);
                ++$order;
            }

            $schema->executeChanges();
        }

        foreach ($indexesToAdd as $object => $indexes) {
            if ($object == 'company') {
                /** @var IndexSchemaHelper $indexHelper */
                $indexHelper = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('index', 'companies');
            } else {
                /** @var IndexSchemaHelper $indexHelper */
                $indexHelper = $this->container->get('mautic.schema.helper.factory')->getSchemaHelper('index', 'leads');
            }

            foreach ($indexes as $name) {
                $type = (isset($fields[$name]['type'])) ? $fields[$name]['type'] : 'text';
                if ('textarea' != $type) {
                    $indexHelper->addIndex([$name], $name.'_search');
                }
            }
            if ($object == 'lead') {
                // Add an attribution index
                $indexHelper->addIndex(['attribution', 'attribution_date'], 'contact_attribution');
                //Add date added and country index
                $indexHelper->addIndex(['date_added', 'country'], 'date_added_country_index');
            } else {
                $indexHelper->addIndex(['companyname', 'companyemail'], 'company_filter');
                $indexHelper->addIndex(['companyname', 'companycity', 'companycountry', 'companystate'], 'company_match');
            }

            $indexHelper->executeChanges();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 4;
    }
}
