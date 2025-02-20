<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class FieldTest extends \PHPUnit\Framework\TestCase
{
    public function testShowForConditionalFieldWithNoParent(): void
    {
        $field = new Field();
        $this->assertTrue($field->showForConditionalField([]));
    }

    public function testShowForConditionalFieldWithParentButNoAlias(): void
    {
        $parentFieldId = '55';
        $field         = new Field();
        $parentField   = $this->createMock(Field::class);
        $form          = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $parentField->method('getId')->willReturn($parentFieldId);

        $this->assertFalse($field->showForConditionalField([]));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndNotInConditionAndBadValue(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'notIn', 'values' => []]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => 'value A'];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasWith0ValueAndNotInConditionAndBadValue(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'notIn', 'values' => [1]]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => 0];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndNotInConditionAndMatchingValue(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'notIn', 'values' => ['value A']]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => 'value A'];

        $this->assertFalse($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndAnyValue(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => '', 'any' => true, 'values' => ['value A']]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => 'value A'];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentValue0AndAliasAndAnyValue(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => '', 'any' => true, 'values' => [1]]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => 0];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndInValueMatches(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'in', 'values' => ['value A']]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => ['value A']];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndInValueDoesNotMatch(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'in', 'values' => ['value B']]);
        $parentField->method('getId')->willReturn(55);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => ['value A']];

        $this->assertFalse($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndInValueMatchesWithDifferentTypes(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $field->setConditions(['expr' => 'in', 'values' => ['0']]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => [0]];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForConditionalFieldWithParentAndAliasAndInValueMatchesSpecialCharacters(): void
    {
        $parentFieldId    = '55';
        $parentFieldAlias = 'field_a';
        $field            = new Field();
        $parentField      = $this->createMock(Field::class);
        $form             = new Form();
        $form->addField(0, $parentField);
        $field->setForm($form);
        $field->setParent($parentFieldId);
        $specialValue = 'čé+äà>&"\'è';
        $field->setConditions(['expr' => 'in', 'values' => [InputHelper::clean($specialValue)]]);
        $parentField->method('getId')->willReturn($parentFieldId);
        $parentField->method('getAlias')->willReturn($parentFieldAlias);
        $data = [$parentFieldAlias => [$specialValue]];

        $this->assertTrue($field->showForConditionalField($data));
    }

    public function testShowForContactIfFormIsNull(): void
    {
        $field = new Field();
        Assert::assertTrue($field->showForContact());
    }

    public function testShowForContactIfInKioskMode(): void
    {
        $field = new Field();
        $form  = new Form();
        $form->setInKioskMode(true);
        Assert::assertTrue($field->showForContact(null, null, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsTrue(): void
    {
        $field = new Field();
        $form  = new Form();
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(true);
        Assert::assertTrue($field->showForContact(null, null, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsFalseAndSubmissionExists(): void
    {
        $field       = new Field();
        $form        = new Form();
        $submissions = [['field_a' => 'Value A']];
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(false);
        $field->setIsAutoFill(false);
        $field->setAlias('field_a');
        Assert::assertFalse($field->showForContact($submissions, null, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsFalseAndSubmissionDoesNotExist(): void
    {
        $field       = new Field();
        $form        = new Form();
        $submissions = [['field_a' => 'Value A']];
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(false);
        $field->setIsAutoFill(false);
        $field->setAlias('unicorn');
        Assert::assertTrue($field->showForContact($submissions, null, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsFalseAndMappedLeadFieldValueExists(): void
    {
        $field   = new Field();
        $form    = new Form();
        $contact = new class extends Lead {
            public function getFieldValue($field, $group = null)
            {
                Assert::assertSame('field_a', $field);

                return 'Value A';
            }
        };
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(false);
        $field->setMappedField('field_a');
        $field->setMappedObject('contact');
        $field->setIsAutoFill(false);
        Assert::assertFalse($field->showForContact(null, $contact, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsFalseAndMappedLeadFieldValueDoesNotExist(): void
    {
        $field   = new Field();
        $form    = new Form();
        $contact = new class extends Lead {
            public function getFieldValue($field, $group = null)
            {
                Assert::assertSame('field_a', $field);

                return null;
            }
        };
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(false);
        $field->setMappedField('field_a');
        $field->setMappedObject('contact');
        $field->setIsAutoFill(false);
        Assert::assertTrue($field->showForContact(null, $contact, $form));
    }

    public function testShowForContactIfShowWhenValueExistsIsFalseAndMappedNotLeadFieldValueExists(): void
    {
        $field   = new Field();
        $form    = new Form();
        $contact = new class extends Lead {
            public function getFieldValue($field, $group = null)
            {
                Assert::assertSame('field_a', $field);

                return 'Value A';
            }
        };
        $form->setInKioskMode(false);
        $field->setShowWhenValueExists(false);
        $field->setMappedField('field_a');
        $field->setMappedObject('unicorn_object');
        $field->setIsAutoFill(false);
        Assert::assertTrue($field->showForContact(null, $contact, $form));
    }

    /**
     * @dataProvider dataProvider
     *
     * @param array<string, int> $properties
     */
    public function testHasChoices(string $type, array $properties, bool $result): void
    {
        $field = new Field();
        $field->setProperties($properties);
        $field->setType($type);

        $this->assertEquals($result, $field->hasChoices());
    }

    /**
     * @return array<int, mixed>
     */
    public static function dataProvider(): iterable
    {
        yield ['string', [], false];
        yield ['string', ['multiple' => 0], false];
        yield ['string', ['multiple' => 1], true];
        yield ['checkboxgrp', [], true];
        yield ['checkboxgrp', ['multiple' => 0], true];
        yield ['checkboxgrp', ['multiple' => 1], true];
    }
}
