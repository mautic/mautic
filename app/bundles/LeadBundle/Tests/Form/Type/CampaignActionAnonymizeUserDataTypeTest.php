<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Form\Type;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;
use Mautic\LeadBundle\Form\Type\CampaignActionAnonymizeUserDataType;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\FormBuilderInterface;

class CampaignActionAnonymizeUserDataTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testBuildForm(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->expects($this->exactly(2))->method('getId')->willReturn(1);
        $lead->expects($this->exactly(2))->method('getLabel')->willReturn('email');

        $fieldsChoices = [
            $lead,
        ];

        $fieldModel       = $this->createMock(FieldModel::class);
        $fieldRepository  = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->expects($this->exactly(2))->method('findBy')->willReturn($fieldsChoices);
        $fieldModel->expects($this->exactly(2))->method('getRepository')->willReturn($fieldRepository);
        $builder    = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->exactly(4))->method('add');
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, []);
    }

    public function testGetBlockPrefix(): void
    {
        $fieldModel                          = $this->createMock(FieldModel::class);
        $translator                          = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);
        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $this->assertEquals('lead_action_anonymizeuserdata', $campaignActionAnonymizeUserDataType->getBlockPrefix());
    }

    /**
     * Test that buildForm uses default values when no data is provided.
     */
    public function testBuildFormWithDefaultValues(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->method('getId')->willReturn(1);
        $lead->method('getLabel')->willReturn('email');

        $fieldsChoices = [$lead];

        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->method('findBy')->willReturn($fieldsChoices);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        // Verify that default values are used
        $builder->expects($this->exactly(4))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) {
                if ('fieldsToDelete' === $name) {
                    $this->assertEquals(
                        CampaignActionAnonymizeUserDataType::DEFAULT_VALUES_TO_DELETE,
                        $options['data']
                    );
                }
                if ('fieldsToAnonymize' === $name) {
                    $this->assertEquals(
                        CampaignActionAnonymizeUserDataType::DEFAULT_VALUES_TO_ANONYMIZE,
                        $options['data']
                    );
                }
                if ('pseudonymize' === $name) {
                    $this->assertFalse($options['data']);
                }

                return $this->createMock(FormBuilderInterface::class);
            });

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => []]);
    }

    /**
     * Test that buildForm uses provided data values instead of defaults.
     */
    public function testBuildFormWithCustomData(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->method('getId')->willReturn(1);
        $lead->method('getLabel')->willReturn('email');

        $fieldsChoices = [$lead];

        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->method('findBy')->willReturn($fieldsChoices);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        $customData = [
            'pseudonymize'      => true,
            'fieldsToDelete'    => ['Custom Field' => 99],
            'fieldsToAnonymize' => ['Another Field' => 88],
        ];

        // Verify that custom values are used
        $builder->expects($this->exactly(4))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) use ($customData) {
                if ('fieldsToDelete' === $name) {
                    $this->assertEquals($customData['fieldsToDelete'], $options['data']);
                }
                if ('fieldsToAnonymize' === $name) {
                    $this->assertEquals($customData['fieldsToAnonymize'], $options['data']);
                }
                if ('pseudonymize' === $name) {
                    $this->assertTrue($options['data']);
                }

                return $this->createMock(FormBuilderInterface::class);
            });

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => $customData]);
    }

    /**
     * Test that getFieldChoices filters unique fields correctly.
     */
    public function testGetFieldChoicesExcludesUniqueFields(): void
    {
        $uniqueField = $this->createMock(LeadField::class);
        $uniqueField->method('getId')->willReturn(1);
        $uniqueField->method('getLabel')->willReturn('Email (Unique)');

        $normalField = $this->createMock(LeadField::class);
        $normalField->method('getId')->willReturn(2);
        $normalField->method('getLabel')->willReturn('First Name');

        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);

        // First call for fieldsToAnonymize (excludeUniqueFields = false)
        // Second call for fieldsToDelete (excludeUniqueFields = true)
        $fieldRepository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnCallback(function ($criteria) use ($uniqueField, $normalField) {
                if (isset($criteria['isUniqueIdentifer']) && false === $criteria['isUniqueIdentifer']) {
                    // For fieldsToDelete - exclude unique fields
                    return [$normalField];
                }

                // For fieldsToAnonymize - include all
                return [$uniqueField, $normalField];
            });

        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => []]);
    }

    /**
     * Test that validation callback is properly attached to fieldsToDelete field.
     */
    public function testValidationCallbackIsAttached(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->method('getId')->willReturn(1);
        $lead->method('getLabel')->willReturn('email');

        $fieldsChoices = [$lead];

        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->method('findBy')->willReturn($fieldsChoices);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        // Verify that constraints are added to fieldsToDelete
        $builder->expects($this->exactly(4))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) {
                if ('fieldsToDelete' === $name) {
                    $this->assertArrayHasKey('constraints', $options);
                    $this->assertIsArray($options['constraints']);
                    $this->assertCount(1, $options['constraints']);
                    $this->assertInstanceOf(
                        \Symfony\Component\Validator\Constraints\Callback::class,
                        $options['constraints'][0]
                    );
                }

                return $this->createMock(FormBuilderInterface::class);
            });

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => []]);
    }

    /**
     * Test that only allowed field types are fetched.
     */
    public function testOnlyAllowedFieldTypesAreQueried(): void
    {
        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);

        $fieldRepository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnCallback(function ($criteria) {
                // Verify that only allowed field types are queried
                $this->assertArrayHasKey('type', $criteria);
                $this->assertEquals(
                    CampaignActionAnonymizeUserDataType::FIELD_TYPE_ALLOWED,
                    $criteria['type']
                );

                return [];
            });

        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => []]);
    }

    /**
     * Test that customText field is properly configured as informational only.
     */
    public function testCustomTextFieldConfiguration(): void
    {
        $lead = $this->createMock(LeadField::class);
        $lead->method('getId')->willReturn(1);
        $lead->method('getLabel')->willReturn('email');

        $fieldModel      = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->method('findBy')->willReturn([$lead]);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        $builder    = $this->createMock(FormBuilderInterface::class);
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);
        $translator->method('trans')->willReturn('Audit log message');

        // Verify customText field configuration
        $builder->expects($this->exactly(4))
            ->method('add')
            ->willReturnCallback(function ($name, $type, $options) {
                if ('customText' === $name) {
                    $this->assertFalse($options['mapped']);
                    $this->assertFalse($options['required']);
                    $this->assertTrue($options['attr']['readonly']);
                    $this->assertStringContainsString('display: none', $options['attr']['style']);
                }

                return $this->createMock(FormBuilderInterface::class);
            });

        $campaignActionAnonymizeUserDataType = new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
        $campaignActionAnonymizeUserDataType->buildForm($builder, ['data' => []]);
    }
}
