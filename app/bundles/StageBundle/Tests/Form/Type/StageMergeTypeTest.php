<?php

namespace Mautic\StageBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\StageBundle\Form\Type\StageMergeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class StageMergeTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'stage_to_merge' => 2,
        ];

        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => [
                'Stage 1' => 1,
                'Stage 2' => 2,
                'Stage 3' => 3,
            ],
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($formData, $form->getData());
    }

    public function testSubmitEmptyData(): void
    {
        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => [
                'Stage 1' => 1,
                'Stage 2' => 2,
            ],
        ]);

        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('stage_to_merge')->getErrors()->count() > 0);
    }

    public function testSubmitInvalidStageId(): void
    {
        $formData = [
            'stage_to_merge' => 999,
        ];

        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => [
                'Stage 1' => 1,
                'Stage 2' => 2,
            ],
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('stage_to_merge')->getErrors()->count() > 0);
    }

    public function testFormStructure(): void
    {
        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => [
                'Stage 1' => 1,
                'Stage 2' => 2,
            ],
        ]);

        $this->assertTrue($form->has('stage_to_merge'));
        $this->assertTrue($form->has('buttons'));

        $stageField = $form->get('stage_to_merge');
        $this->assertInstanceOf(ChoiceType::class, $stageField->getConfig()->getType()->getInnerType());

        $buttonsField = $form->get('buttons');
        $this->assertInstanceOf(FormButtonsType::class, $buttonsField->getConfig()->getType()->getInnerType());
    }

    public function testFormOptions(): void
    {
        $stages = [
            'Stage 1' => 1,
            'Stage 2' => 2,
            'Stage 3' => 3,
        ];

        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => $stages,
        ]);

        $stageField = $form->get('stage_to_merge');
        $config = $stageField->getConfig();

        $this->assertEquals($stages, $config->getOption('choices'));
        $this->assertFalse($config->getOption('multiple'));
        $this->assertTrue($config->getOption('required'));
        $this->assertEquals('mautic.stage.to.merge.into', $config->getOption('label'));
    }

    public function testFormButtonsOptions(): void
    {
        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => ['Stage 1' => 1],
        ]);

        $buttonsField = $form->get('buttons');
        $config = $buttonsField->getConfig();

        $this->assertFalse($config->getOption('apply_text'));
        $this->assertEquals('mautic.lead.merge', $config->getOption('save_text'));
        $this->assertEquals('ri-flag-line', $config->getOption('save_icon'));
    }

    public function testFormWithAction(): void
    {
        $action = '/test/action';
        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => ['Stage 1' => 1],
            'action' => $action,
        ]);

        $this->assertEquals($action, $form->getConfig()->getOption('action'));
    }

    public function testFormValidation(): void
    {
        $form = $this->factory->create(StageMergeType::class, [], [
            'stages' => ['Stage 1' => 1],
        ]);

        $form->submit([]);

        $this->assertFalse($form->isValid());
        $this->assertTrue($form->get('stage_to_merge')->getErrors()->count() > 0);

        $errors = $form->get('stage_to_merge')->getErrors();
        $this->assertStringContainsString('required', $errors->current()->getMessage());
    }
}
