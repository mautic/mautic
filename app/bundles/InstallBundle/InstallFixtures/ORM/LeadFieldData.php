<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LeadFieldData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface, FixtureGroupInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function load(ObjectManager $manager)
    {
        $fieldGroups['lead']    = FieldModel::$coreFields;
        $fieldGroups['company'] = FieldModel::$coreCompanyFields;

        $translator   = $this->container->get('translator');
        foreach ($fieldGroups as $fields) {
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

                if (isset($field['default'])) {
                    $entity->setDefaultValue($field['default']);
                }

                $manager->persist($entity);
                $manager->flush();

                if (!$this->hasReference('leadfield-'.$alias)) {
                    $this->addReference('leadfield-'.$alias, $entity);
                }
                ++$order;
            }
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
