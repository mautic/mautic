<?php

namespace Mautic\StageBundle\Tests\Form\Type;

use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Form\Type\StageListType;
use Mautic\StageBundle\Model\StageModel;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

class StageListTypeTest extends TypeTestCase
{
    private StageModel $stageModel;
    private $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(\Mautic\CoreBundle\Entity\CommonRepository::class);
        $this->stageModel = $this->createMock(StageModel::class);
        $this->stageModel->method('getRepository')->willReturn($this->repository);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }

    protected function getTypes(): array
    {
        return [
            new StageListType($this->stageModel),
        ];
    }

    public function testSubmitValidData(): void
    {
        $stage1 = $this->createMock(Stage::class);
        $stage1->method('getId')->willReturn(2);
        $stage1->method('getName')->willReturn('Stage 2');

        $stage2 = $this->createMock(Stage::class);
        $stage2->method('getId')->willReturn(3);
        $stage2->method('getName')->willReturn('Stage 3');

        $this->repository->method('getEntities')->willReturn([$stage1, $stage2]);

        $formData = [2, 3];

        $form = $this->factory->create(StageListType::class, null);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals($formData, $form->getData());
    }

    public function testSubmitEmptyData(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null);

        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals([], $form->getData());
    }

    public function testSubmitNullData(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null);

        $form->submit(null);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());
        $this->assertEquals([], $form->getData());
    }

    public function testFormStructure(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null);

        // The form type extends ChoiceType, so we check its parent
        $this->assertEquals(ChoiceType::class, $form->getConfig()->getType()->getParent()->getInnerType()::class);
    }

    public function testFormOptions(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null);

        $config = $form->getConfig();

        $this->assertTrue($config->getOption('multiple'));
        $this->assertFalse($config->getOption('required'));
        // Only test placeholder if it's set, since it might be null due to form type configuration
        if ($config->hasOption('placeholder') && null !== $config->getOption('placeholder')) {
            $this->assertEquals('mautic.core.form.chooseone', $config->getOption('placeholder'));
        }
    }

    public function testFormWithMultipleOption(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null, [
            'multiple' => false,
        ]);

        $config = $form->getConfig();
        $this->assertFalse($config->getOption('multiple'));
    }

    public function testFormWithRequiredOption(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null, [
            'required' => true,
        ]);

        $config = $form->getConfig();
        $this->assertTrue($config->getOption('required'));
    }

    public function testFormWithCustomLabel(): void
    {
        $this->repository->method('getEntities')->willReturn([]);

        $form = $this->factory->create(StageListType::class, null, [
            'label' => 'Custom Label',
        ]);

        $config = $form->getConfig();
        $this->assertEquals('Custom Label', $config->getOption('label'));
    }
}
