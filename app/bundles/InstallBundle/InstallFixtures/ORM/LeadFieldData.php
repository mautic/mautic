<?php

namespace Mautic\InstallBundle\InstallFixtures\ORM;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Contracts\Translation\TranslatorInterface;

class LeadFieldData extends AbstractFixture implements OrderedFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public static function getGroups(): array
    {
        return ['group_install', 'group_mautic_install_data'];
    }

    /**
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function load(ObjectManager $manager): void
    {
        $fieldGroups['lead']    = FieldModel::$coreFields;
        $fieldGroups['company'] = FieldModel::$coreCompanyFields;

        foreach ($fieldGroups as $fields) {
            $order = 1;
            foreach ($fields as $alias => $field) {
                $type = $field['type'] ?? 'text';

                $entity = new LeadField();
                $entity->setLabel($this->translator->trans('mautic.lead.field.'.$alias, [], 'fixtures'));
                $entity->setGroup($field['group'] ?? 'core');
                $entity->setOrder($order);
                $entity->setAlias($alias);
                $entity->setIsRequired($field['required'] ?? false);
                $entity->setType($type);
                $entity->setObject($field['object']);
                $entity->setIsUniqueIdentifer(!empty($field['unique']));
                $entity->setProperties($field['properties'] ?? []);
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

    public function getOrder()
    {
        return 4;
    }
}
