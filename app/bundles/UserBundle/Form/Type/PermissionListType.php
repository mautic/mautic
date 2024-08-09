<?php

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class PermissionListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['bundle', 'level']);

        $resolver->setDefaults([
            'multiple'          => true,
            'expanded'          => true,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => fn (Options $options): array => [
                'data-permission' => $options['bundle'].':'.$options['level'],
                'onchange'        => 'Mautic.onPermissionChange(this, \''.$options['bundle'].'\')',
            ],
            'choices_as_values' => false,
        ]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'permissionlist';
    }
}
