<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\FormBundle\Form\Type\FormFieldBooleanType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

final class FormFieldBooleanTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        return [
            new ValidatorExtension(Validation::createValidator()),
            new PreloadedExtension([
                FormFieldBooleanType::class => new FormFieldBooleanType(),
            ], []),
        ];
    }

    public function testSubmitValidData(): void
    {
        $formData = [
            'yes' => 'I agree',
            'no'  => 'I do not agree',
        ];

        $form = $this->factory->create(FormFieldBooleanType::class);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame($formData, $form->getData());

        $view = $form->createView();

        foreach (array_keys($formData) as $key) {
            $this->assertArrayHasKey($key, $view->children);
        }
    }

    public function testUsesExistingDataForLabels(): void
    {
        $form = $this->factory->create(FormFieldBooleanType::class, [
            'yes' => 'Subscribe me',
            'no'  => '',
        ]);

        $this->assertSame('Subscribe me', $form->get('yes')->getData());
        $this->assertSame('', $form->get('no')->getData());
    }
}
