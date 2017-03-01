<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotImageType.
 */
class SlotSocialFollowType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add(
            'glink',
            TextType::class,
            [
                'label'      => 'mautic.core.googleplus.url',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'value'           => 'http://plus.google.com',
                    'class'           => 'form-control',
                    'data-slot-param' => 'glink',
                ],
            ]
        );

        $builder->add(
            'flink',
            TextType::class,
            [
                'label'      => 'mautic.core.facebook.url',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'value'           => 'http://www.facebook.com',
                    'class'           => 'form-control',
                    'data-slot-param' => 'flink',
                ],
            ]
        );

        $builder->add(
            'tlink',
            TextType::class,
            [
                'label'      => 'mautic.core.twitter.url',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'value'           => 'http://www.twitter.com',
                    'class'           => 'form-control',
                    'data-slot-param' => 'tlink',
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_socialfollow';
    }
}
