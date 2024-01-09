<?php

namespace Mautic\DynamicContentBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class DynamicContentListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_dynamicContent_action',
                'modal_header'        => 'mautic.dynamicContent.header.new',
                'model'               => 'dynamicContent',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'    => 'dynamicContent',
                    'filter'  => '$data',
                    'limit'   => 0,
                    'start'   => 0,
                    'options' => [
                        'top_level'  => $options['top_level'],
                        'ignore_ids' => $options['ignore_ids'],
                        'where'      => $options['where'],
                    ],
                ],
                'ajax_lookup_action' => function (Options $options): string {
                    $query = [
                        'top_level'  => $options['top_level'],
                        'ignore_ids' => $options['ignore_ids'],
                        'where'      => $options['where'],
                    ];

                    return 'dynamicContent:getLookupChoiceList&'.http_build_query($query);
                },
                'multiple'   => false,
                'required'   => false,
                'top_level'  => 'translation',
                'ignore_ids' => [],
                'where'      => '',
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'dwc_list';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return EntityLookupType::class;
    }
}
