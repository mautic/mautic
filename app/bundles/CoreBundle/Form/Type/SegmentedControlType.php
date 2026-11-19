<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
final class SegmentedControlType extends AbstractType
{
    public function getParent(): string
    {
        return ButtonGroupType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'segmented_control';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'size'        => 'md',
            'equal_width' => false,
            'draggable'   => true,
            'duration'    => null,
            'icons'       => [],
        ]);
        $resolver->setAllowedValues('size', ['sm', 'md', 'lg']);
        $resolver->setAllowedTypes('equal_width', 'bool');
        $resolver->setAllowedTypes('draggable', 'bool');
        $resolver->setAllowedTypes('duration', ['null', 'int', 'float']);
        $resolver->setAllowedTypes('icons', 'array');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['segmented_control'] = [
            'size'       => $options['size'],
            'equalWidth' => $options['equal_width'],
            'draggable'  => $options['draggable'],
            'duration'   => $options['duration'],
            'icons'      => $options['icons'],
        ];
    }
}
