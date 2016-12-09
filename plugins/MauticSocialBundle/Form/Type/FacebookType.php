<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FacebookType.
 */
class FacebookType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('layout', 'choice', [
            'choices' => [
                'standard'     => 'mautic.integration.Facebook.share.layout.standard',
                'button_count' => 'mautic.integration.Facebook.share.layout.buttoncount',
                'button'       => 'mautic.integration.Facebook.share.layout.button',
                'box_count'    => 'mautic.integration.Facebook.share.layout.boxcount',
                'icon'         => 'mautic.integration.Facebook.share.layout.icon',
            ],
            'label'       => 'mautic.integration.Facebook.share.layout',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
        ]);

        $builder->add('action', 'choice', [
            'choices' => [
                'like'      => 'mautic.integration.Facebook.share.action.like',
                'recommend' => 'mautic.integration.Facebook.share.action.recommend',
                'share'     => 'mautic.integration.Facebook.share.action.share',
            ],
            'label'       => 'mautic.integration.Facebook.share.action',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
        ]);

        $builder->add('showFaces', 'yesno_button_group', [
            'label' => 'mautic.integration.Facebook.share.showfaces',
            'data'  => (!isset($options['data']['showFaces'])) ? 1 : $options['data']['showFaces'],
        ]);

        $builder->add('showShare', 'yesno_button_group', [
            'label' => 'mautic.integration.Facebook.share.showshare',
            'data'  => (!isset($options['data']['showShare'])) ? 1 : $options['data']['showShare'],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'socialmedia_facebook';
    }
}
