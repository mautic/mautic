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

class LinkedInType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'counter',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.integration.LinkedIn.share.counter.right' => 'right',
                    'mautic.integration.LinkedIn.share.counter.top'   => 'top',
                    'mautic.integration.LinkedIn.share.counter.none'  => '',
                ],
                'label'       => 'mautic.integration.LinkedIn.share.counter',
                'required'    => false,
                'placeholder' => false,
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'socialmedia_linkedin';
    }
}
