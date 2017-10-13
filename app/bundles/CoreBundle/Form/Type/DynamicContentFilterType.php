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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DynamicContentFilterType.
 */
class DynamicContentFilterType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'tokenName',
            'text',
            [
                'label' => 'mautic.core.dynamicContent.token_name',
                'attr'  => [
                    'class' => 'form-control dynamic-content-token-name',
                ],
                'read_only' => true,
            ]
        );

        $builder->add(
            'content',
            'textarea',
            [
                'label' => 'mautic.core.dynamicContent.default_content',
                'attr'  => [
                    'class' => 'form-control editor editor-dynamic-content',
                ],
            ]
        );

        $builder->add(
            $builder->create(
                'filters',
                'collection',
                [
                    'type'    => 'dynamic_content_filter_entry',
                    'options' => [
                        'label' => false,
                        'attr'  => [
                            'class' => 'form-control',
                        ],
                    ],
                    'allow_add'    => true,
                    'allow_delete' => true,
                ]
            )
        );
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'dynamic_content_filter';
    }
}
