<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @deprecated since Mautic 5.0, to be removed in 6.0 with no replacement.
 */
class TwitterCustomType extends TwitterAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('custom', TextType::class, [
            'label'      => 'mautic.social.monitoring.twitter.custom',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.social.monitoring.twitter.custom.tooltip',
                'preaddon' => 'fa fa-crosshairs',
            ],
        ]);

        // pull in the parent type's form builder
        parent::buildForm($builder, $options);
    }

    public function getBlockPrefix()
    {
        return 'twitter_custom';
    }
}
