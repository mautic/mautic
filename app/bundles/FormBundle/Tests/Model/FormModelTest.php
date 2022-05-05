<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Tests\FormTestAbstract;
use Mautic\LeadBundle\Entity\LeadField;

class FormModelTest extends FormTestAbstract
{
    public function testSetFields()
    {
        $form      = new Form();
        $fields    = $this->getTestFormFields();
        $formModel = $this->getFormModel();
        $formModel->setFields($form, $fields);
        $entityFields = $form->getFields()->toArray();

        /** @var Field $newField */
        $newField = $entityFields[array_keys($fields)[0]];

        /** @var Field $fileField */
        $fileField = $entityFields[array_keys($fields)[1]];

        /** @var Field $parentField */
        $parentField = $entityFields[array_keys($fields)[2]];

        /** @var Field $childField */
        $childField = $entityFields[array_keys($fields)[3]];

        /** @var Field $childField */
        $newChildField = $entityFields[array_keys($fields)[4]];

        $this->assertInstanceOf(Field::class, $newField);
        $this->assertSame('email', $newField->getType());
        $this->assertSame('email', $newField->getAlias());
        $this->assertSame(1, $newField->getOrder());
        $this->assertSame('file', $fileField->getType());
        $this->assertSame('file', $fileField->getAlias());
        $this->assertSame(2, $fileField->getOrder());
        $this->assertSame('select', $parentField->getType());
        $this->assertSame('parent', $parentField->getAlias());
        $this->assertSame(3, $parentField->getOrder());
        $this->assertSame('text', $childField->getType());
        $this->assertSame('child', $childField->getAlias());
        $this->assertSame(4, $childField->getOrder());
        $this->assertSame('text', $newChildField->getType());
        $this->assertSame('new_child', $newChildField->getAlias());
        $this->assertSame(4, $newChildField->getOrder());
    }

    public function testGetComponentsFields()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('fields', $components);
    }

    public function testGetComponentsActions()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('actions', $components);
    }

    public function testGetComponentsValidators()
    {
        $formModel  = $this->getFormModel();
        $components = $formModel->getCustomComponents();
        $this->assertArrayHasKey('validators', $components);
    }

    public function testGetEntityForNotFoundContactField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactselect');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn(null);

        $formModel->getEntity(5);

        $this->assertSame(['syncList' => true], $formField->getProperties());
    }

    public function testGetEntityForNotLinkedSelectField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $formModel->getEntity(5);
    }

    public function testGetEntityForNotSyncedSelectField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactselect');
        $formField->setProperties(['syncList' => false]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->never())
            ->method('getEntityByAlias');

        $formModel->getEntity(5);
    }

    public function testGetEntityForSyncedBooleanField()
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = ['no' => 'lunch?', 'yes' => 'dinner?'];

        $formField = new Field();
        $formField->setLeadField('contactbool');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType('boolean');
        $contactField->setProperties($options);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactbool')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        $this->assertSame(['lunch?', 'dinner?'], $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedCountryField()
    {
        $formField = $this->standardSyncListStaticFieldTest('country');

        $this->assertArrayHasKey('Czech Republic', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedRegionField()
    {
        $formField = $this->standardSyncListStaticFieldTest('region');

        $this->assertArrayHasKey('Canada', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedTimezoneField()
    {
        $formField = $this->standardSyncListStaticFieldTest('timezone');

        $this->assertArrayHasKey('Africa', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForSyncedLocaleField()
    {
        $formField = $this->standardSyncListStaticFieldTest('locale');

        $this->assertArrayHasKey('Czech (Czechia)', $formField->getProperties()['list']['list']);
    }

    public function testGetEntityForLinkedSyncListFields()
    {
        $this->standardSyncListFieldTest('select');
        $this->standardSyncListFieldTest('multiselect');
        $this->standardSyncListFieldTest('lookup');
    }

    private function standardSyncListFieldTest($type)
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();
        $options    = [
            ['label' => 'label1', 'value' => 'value1'],
            ['label' => 'label2', 'value' => 'value2'],
        ];

        $formField = new Field();
        $formField->setLeadField('contactfieldalias');
        $formField->setProperties(['syncList' => true]);

        $contactField = new LeadField();
        $contactField->setType($type);
        $contactField->setProperties(['list' => $options]);

        $fields->add($formField);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfieldalias')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        $this->assertSame($options, $formField->getProperties()['list']['list']);
    }

    private function standardSyncListStaticFieldTest($type)
    {
        $formModel  = $this->getFormModel();
        $formEntity = $this->createMock(Form::class);
        $fields     = new ArrayCollection();

        $formField = new Field();
        $formField->setLeadField('contactfield');
        $formField->setProperties(['syncList' => true]);

        $fields->add($formField);

        $contactField = new LeadField();
        $contactField->setType($type);

        $formEntity->expects($this->exactly(2))
            ->method('getFields')
            ->willReturn($fields);

        $this->formRepository->expects($this->once())
            ->method('getEntity')
            ->with(5)
            ->willReturn($formEntity);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->with('contactfield')
            ->willReturn($contactField);

        $formModel->getEntity(5);

        return $formField;
    }

    public function testGetContactFieldPropertiesListWhenFieldNotFound(): void
    {
        $formModel = $this->getFormModel();

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias');

        $this->assertNull($formModel->getContactFieldPropertiesList('alias_a'));
    }

    public function testGetContactFieldPropertiesListWhenFieldFoundButNotList(): void
    {
        $formModel = $this->getFormModel();
        $field     = new LeadField();
        $field->setType('text');

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn($field);

        $this->assertNull($formModel->getContactFieldPropertiesList('alias_a'));
    }

    public function testGetContactFieldPropertiesListWhenSelectFieldFound(): void
    {
        $formModel = $this->getFormModel();
        $field     = new LeadField();
        $field->setType('select');
        $field->setProperties(['list' => ['choice_a' => 'Choice A']]);

        $this->leadFieldModel->expects($this->once())
            ->method('getEntityByAlias')
            ->willReturn($field);

        $this->assertSame(
            ['choice_a' => 'Choice A'],
            $formModel->getContactFieldPropertiesList('alias_a')
        );
    }
}
