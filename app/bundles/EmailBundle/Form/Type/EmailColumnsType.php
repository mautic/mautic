<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\Services\EmailColumnsDictionary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class EmailColumnsType extends AbstractType
{
    public function __construct(
        private readonly EmailColumnsDictionary $columnsDictionary,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'choices'    => array_flip($this->columnsDictionary->getFields()),
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => true,
                'expanded'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
