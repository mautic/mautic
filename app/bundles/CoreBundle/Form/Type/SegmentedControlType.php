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
            'draggable' => true,
        ]);
        $resolver->setAllowedTypes('draggable', 'bool');
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['segmented_control'] = [
            'draggable' => $options['draggable'],
        ];
    }
}
