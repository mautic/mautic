<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

class LeadFieldRepositoryFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testCompareValueEqualsOperator(): void
    {
        $lead = new Lead();
        $lead->setFirstname('John');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'firstname', 'John', 'eq'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'firstname', 'Jack', 'eq'));
    }

    public function testCompareValueNotEqualsOperator(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Ada');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'firstname', 'Annie', 'neq'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'firstname', 'Ada', 'neq'));
    }

    public function testCompareValueEmptyOperator(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Ada');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'lastname', null, 'empty'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'firstname', null, 'empty'));
    }

    public function testCompareValueNotEmptyOperator(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Ada');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'firstname', null, 'notEmpty'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'lastname', null, 'notEmpty'));
    }

    public function testCompareValueStartsWithOperator(): void
    {
        $lead = new Lead();
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'email', 'Mary', 'startsWith'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'email', 'Unicorn', 'startsWith'));
    }

    public function testCompareValueEndWithOperator(): void
    {
        $lead = new Lead();
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'email', 'armyspy.com', 'endsWith'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'email', 'Unicorn', 'endsWith'));
    }

    public function testCompareValueContainsOperator(): void
    {
        $lead = new Lead();
        $lead->setEmail('MaryWNevarez@armyspy.com');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'email', 'Nevarez', 'contains'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'email', 'Unicorn', 'contains'));
    }

    public function testCompareValueInOperator(): void
    {
        $lead = new Lead();
        $lead->setCountry('United Kingdom');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'country', ['United Kingdom', 'South Africa'], 'in'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'country', ['Poland', 'Canada'], 'in'));
    }

    public function testCompareValueNotInOperator(): void
    {
        $lead = new Lead();
        $lead->setCountry('United Kingdom');
        $this->em->persist($lead);
        $this->em->flush();

        $repository = static::getContainer()->get('mautic.lead.model.field')->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'country', ['Australia', 'Poland'], 'notIn'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'country', ['United Kingdom'], 'notIn'));
    }

    public function testCompareValueInOperatorWithMultiselectField(): void
    {
        $field = new LeadField();
        $field->setType('multiselect');
        $field->setObject('lead');
        $field->setAlias('colors');
        $field->setName('Colors');
        $field->setProperties(
            [
                'list' => [
                    [
                        'label' => 'Red',
                        'value' => 'red',
                    ], [
                        'label' => 'Green',
                        'value' => 'green',
                    ], [
                        'label' => 'Blue',
                        'value' => 'blue',
                    ], [
                        'label' => 'Yellow',
                        'value' => 'yellow',
                    ],
                ],
            ]
        );

        $fieldModel = self::getContainer()->get(FieldModel::class);
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);

        $lead = new Lead();
        $lead->addUpdatedField('colors', 'green|blue');
        $contactModel = self::getContainer()->get(LeadModel::class);
        \assert($contactModel instanceof LeadModel);

        $contactModel->saveEntity($lead);
        $repository = $fieldModel->getRepository();

        $this->assertTrue($repository->compareValue($lead->getId(), 'colors', ['green', 'blue'], 'in'));
        $this->assertFalse($repository->compareValue($lead->getId(), 'colors', ['red', 'yellow'], 'in'));
    }

    public function testExcludeUnpublishedField(): void
    {
        $field = new LeadField();
        $field->setType('text');
        $field->setObject('lead');
        $field->setAlias('colors');
        $field->setName('Colors');
        $field->setIsPublished(false);

        $fieldModel = self::getContainer()->get(FieldModel::class);
        $fieldModel->saveEntity($field);
        $repository      = $fieldModel->getRepository();
        $allLeadFields   = $repository->getFieldsForObject('lead');
        $colorFieldExist = false;
        if (!empty($allLeadFields)) {
            foreach ($allLeadFields as $field) {
                if ('colors' == $field->getAlias()) {
                    $colorFieldExist = true;
                }
            }
        }
        $this->assertFalse($colorFieldExist);
    }
}
