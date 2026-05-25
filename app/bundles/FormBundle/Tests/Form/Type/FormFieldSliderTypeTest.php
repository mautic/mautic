<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Form\Type\FormFieldSliderType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class FormFieldSliderTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                FormFieldSliderType::class => new FormFieldSliderType(),
            ], []),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'min'  => 0,
            'max'  => 50,
            'step' => 5,
        ];
        $form = $this->factory->create(FormFieldSliderType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertNotEmpty($form->getData());

        $view     = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $children);
        }
    }

    public function testSubmitInvalidData(): void
    {
        $form = $this->factory->create(FormFieldSliderType::class);

        $invalidData = [
            'min'  => 10,
            'max'  => 5,
            'step' => 15,
        ];

        $form->submit($invalidData);

        $this->assertTrue($form->isSynchronized());
        $this->assertFalse($form->isValid());

        $errors = $form->getErrors(true);
        $this->assertGreaterThan(0, count($errors));
    }
}
