<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TwitterMentionType extends TwitterAbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('handle', TextType::class, [
            'label'      => 'mautic.social.monitoring.twitter.handle',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'    => 'form-control',
                'tooltip'  => 'mautic.social.monitoring.twitter.handle.tooltip',
                'preaddon' => 'fa fa-at',
            ],
        ]);

        $builder->add('checknames', ChoiceType::class, [
            'choices' => [
                'mautic.social.monitoring.twitter.no'  => '0',
                'mautic.social.monitoring.twitter.yes' => '1',
            ],
            'label'             => 'mautic.social.monitoring.twitter.namematching',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.social.monitoring.twitter.namematching.tooltip',
            ],
        ]);

        // pull in the parent type's form builder
        parent::buildForm($builder, $options);
    }

    public function getBlockPrefix()
    {
        return 'twitter_handle';
    }
}
