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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class GooglePlusType.
 */
class GooglePlusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('annotation', ChoiceType::class, [
            'choices' => [
                'mautic.integration.GooglePlus.share.annotation.inline'         => 'inline',
                'mautic.integration.GooglePlus.share.annotation.bubble'         => 'bubble',
                'mautic.integration.GooglePlus.share.annotation.verticalbubble' => 'vertical-bubble',
                'mautic.integration.GooglePlus.share.annotation.none'           => 'none',
            ],
            'label'             => 'mautic.integration.GooglePlus.share.annotation',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
        ]);

        $builder->add('height', ChoiceType::class, [
            'choices' => [
                'mautic.integration.GooglePlus.share.height.standard' => '',
                'mautic.integration.GooglePlus.share.height.small'    => '15',
                'mautic.integration.GooglePlus.share.height.large'    => '24',
            ],
            'label'             => 'mautic.integration.GooglePlus.share.height',
            'required'          => false,
            'placeholder'       => false,
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => ['class' => 'form-control'],
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'socialmedia_googleplus';
    }
}
