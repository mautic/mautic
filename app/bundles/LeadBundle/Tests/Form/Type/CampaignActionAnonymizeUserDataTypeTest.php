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
    private function createLeadFieldMock(int $id = 1, string $label = 'email'): LeadField
    {
        $lead = $this->createMock(LeadField::class);
        $lead->method('getId')->willReturn($id);
        $lead->method('getLabel')->willReturn($label);

        return $lead;
    }

    private function createFieldModelWithRepository(array $fieldsChoices, int $findByCalls = 2): FieldModel
    {
        $fieldModel = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->expects($this->exactly($findByCalls))
            ->method('findBy')
            ->willReturn($fieldsChoices);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        return $fieldModel;
    }

    private function createFieldModelWithRepositoryCallback(callable $callback, int $findByCalls = 2): FieldModel
    {
        $fieldModel = $this->createMock(FieldModel::class);
        $fieldRepository = $this->createMock(LeadFieldRepository::class);
        $fieldRepository->expects($this->exactly($findByCalls))
            ->method('findBy')
            ->willReturnCallback($callback);
        $fieldModel->method('getRepository')->willReturn($fieldRepository);

        return $fieldModel;
    }

    private function createBuilderMockForAdd(int $expectedAddCalls = 4, ?callable $callback = null): FormBuilderInterface
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $expect = $builder->expects($this->exactly($expectedAddCalls))->method('add');
        if ($callback) {
            $expect->willReturnCallback($callback);
        } else {
            $expect->willReturn($this->createMock(FormBuilderInterface::class));
        }

        return $builder;
    }

    private function createTranslatorMock(?string $transReturn = null)
    {
        $translator = $this->createMock(\Mautic\CoreBundle\Translation\Translator::class);
        if (null !== $transReturn) {
            $translator->method('trans')->willReturn($transReturn);
        }

        return $translator;
    }

    private function createTypeInstance(FieldModel $fieldModel, $translator): CampaignActionAnonymizeUserDataType
    {
        return new CampaignActionAnonymizeUserDataType($fieldModel, $translator);
    }

    public function testBuildForm(): void
    {
        $lead = $this->createLeadFieldMock();
        $fieldModel = $this->createFieldModelWithRepository([$lead], 2);
        $builder = $this->createBuilderMockForAdd(4);
        $translator = $this->createTranslatorMock();

        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, []);
    }

    public function testGetBlockPrefix(): void
    {
        $fieldModel = $this->createMock(FieldModel::class);
        $translator = $this->createTranslatorMock();
        $type = $this->createTypeInstance($fieldModel, $translator);

        $this->assertEquals('lead_action_anonymizeuserdata', $type->getBlockPrefix());
    }

    public function testBuildFormWithDefaultValues(): void
    {
        $lead = $this->createLeadFieldMock();
        $fieldModel = $this->createFieldModelWithRepository([$lead], 2);

        $builder = $this->createBuilderMockForAdd(4, function ($name, $type, $options) {
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

        $translator = $this->createTranslatorMock();
        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => []]);
    }

    public function testBuildFormWithCustomData(): void
    {
        $lead = $this->createLeadFieldMock();
        $fieldModel = $this->createFieldModelWithRepository([$lead], 2);

        $customData = [
            'pseudonymize'      => true,
            'fieldsToDelete'    => ['Custom Field' => 99],
            'fieldsToAnonymize' => ['Another Field' => 88],
        ];

        $builder = $this->createBuilderMockForAdd(4, function ($name, $type, $options) use ($customData) {
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

        $translator = $this->createTranslatorMock();
        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => $customData]);
    }

    public function testGetFieldChoicesExcludesUniqueFields(): void
    {
        $uniqueField = $this->createLeadFieldMock(1, 'Email (Unique)');
        $normalField = $this->createLeadFieldMock(2, 'First Name');

        $fieldModel = $this->createFieldModelWithRepositoryCallback(function ($criteria) use ($uniqueField, $normalField) {
            if (isset($criteria['isUniqueIdentifer']) && false === $criteria['isUniqueIdentifer']) {
                return [$normalField];
            }
            return [$uniqueField, $normalField];
        }, 2);

        $builder = $this->createBuilderMockForAdd(4);
        $translator = $this->createTranslatorMock();

        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => []]);
    }

    public function testValidationCallbackIsAttached(): void
    {
        $lead = $this->createLeadFieldMock();
        $fieldModel = $this->createFieldModelWithRepository([$lead], 2);

        $builder = $this->createBuilderMockForAdd(4, function ($name, $type, $options) {
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

        $translator = $this->createTranslatorMock();
        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => []]);
    }

    public function testOnlyAllowedFieldTypesAreQueried(): void
    {
        $fieldModel = $this->createFieldModelWithRepositoryCallback(function ($criteria) {
            $this->assertArrayHasKey('type', $criteria);
            $this->assertEquals(
                CampaignActionAnonymizeUserDataType::FIELD_TYPE_ALLOWED,
                $criteria['type']
            );
            return [];
        }, 2);

        $builder = $this->createBuilderMockForAdd(4);
        $translator = $this->createTranslatorMock();

        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => []]);
    }

    public function testCustomTextFieldConfiguration(): void
    {
        $lead = $this->createLeadFieldMock();
        $fieldModel = $this->createFieldModelWithRepository([$lead], 2);

        $builder = $this->createBuilderMockForAdd(4, function ($name, $type, $options) {
            if ('customText' === $name) {
                $this->assertFalse($options['mapped']);
                $this->assertFalse($options['required']);
                $this->assertTrue($options['attr']['readonly']);
                $this->assertStringContainsString('display: none', $options['attr']['style']);
            }
            return $this->createMock(FormBuilderInterface::class);
        });

        $translator = $this->createTranslatorMock('Audit log message');
        $type = $this->createTypeInstance($fieldModel, $translator);
        $type->buildForm($builder, ['data' => []]);
    }
}
