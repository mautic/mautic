<?php

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class FacebookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('layout', ChoiceType::class, [
            'choices' => [
                'mautic.integration.Facebook.share.layout.standard'    => 'standard',
                'mautic.integration.Facebook.share.layout.buttoncount' => 'button_count',
                'mautic.integration.Facebook.share.layout.button'      => 'button',
                'mautic.integration.Facebook.share.layout.boxcount'    => 'box_count',
                'mautic.integration.Facebook.share.layout.icon'        => 'icon',
            ],
            'label'             => 'mautic.integration.Facebook.share.layout',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
        ]);

        $builder->add('action', ChoiceType::class, [
            'choices' => [
                'mautic.integration.Facebook.share.action.like'      => 'like',
                'mautic.integration.Facebook.share.action.recommend' => 'recommend',
                'mautic.integration.Facebook.share.action.share'     => 'share',
            ],
            'label'             => 'mautic.integration.Facebook.share.action',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
        ]);

        $builder->add('showFaces', YesNoButtonGroupType::class, [
            'label' => 'mautic.integration.Facebook.share.showfaces',
            'data'  => (!isset($options['data']['showFaces'])) ? 1 : $options['data']['showFaces'],
        ]);

        $builder->add('showShare', YesNoButtonGroupType::class, [
            'label' => 'mautic.integration.Facebook.share.showshare',
            'data'  => (!isset($options['data']['showShare'])) ? 1 : $options['data']['showShare'],
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'socialmedia_facebook';
    }
}
