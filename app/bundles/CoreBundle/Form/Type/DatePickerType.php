<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class DatePickerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'widget'     => 'single_text',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'html5'    => false,
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return DateTimeType::class;
    }
}
