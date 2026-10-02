<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
final class ClearbitKeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'apikey',
            TextType::class,
            [
                'label'      => 'mautic.integration.clearbit.apikey',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'auto_update',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.plugin.clearbit.auto_update',
                'attr'  => [
                    'tooltip' => 'mautic.plugin.clearbit.auto_update.tooltip',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
