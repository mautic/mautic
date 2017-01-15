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
 * Class LinkedInType.
 */
class LinkedInType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('counter', 'choice', [
            'choices' => [
                'right' => 'mautic.integration.LinkedIn.share.counter.right',
                'top'   => 'mautic.integration.LinkedIn.share.counter.top',
                ''      => 'mautic.integration.LinkedIn.share.counter.none',
            ],
            'label'       => 'mautic.integration.LinkedIn.share.counter',
            'required'    => false,
            'empty_value' => false,
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'socialmedia_linkedin';
    }
}
