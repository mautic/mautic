<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\SortableListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EmailOpenType.
 */
class FocusOpenDecisionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'urls',
            SortableListType::class,
            [
                'label' => 'mautic.email.click.urls.contains',
                'option_required' => false,
                'with_labels' => false,
                'required' => false,
            ]
        );

        $builder->add(
            'focus',
             FocusShowType::class,[
                'required' => false,
                'label'=>false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'focus_open_decision';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefined(['update_select']);
    }
}
