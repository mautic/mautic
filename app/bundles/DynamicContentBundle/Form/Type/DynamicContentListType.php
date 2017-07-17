<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DynamicContentListType.
 */
class DynamicContentListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_dynamicContent_action',
                'modal_header'        => 'mautic.dynamicContent.header.new',
                'model'               => 'dynamicContent',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options) {
                    return [
                        'type'    => 'dynamicContent',
                        'filter'  => '$data',
                        'limit'   => 0,
                        'start'   => 0,
                        'options' => [
                            'top_level'  => $options['top_level'],
                            'ignore_ids' => $options['ignore_ids'],
                        ],
                    ];
                },
                'ajax_lookup_action' => function (Options $options) {
                    $query = [
                        'top_level'  => $options['top_level'],
                        'ignore_ids' => $options['ignore_ids'],
                    ];

                    return 'dynamicContent:getLookupChoiceList&'.http_build_query($query);
                },
                'multiple'   => false,
                'required'   => false,
                'top_level'  => 'translation',
                'ignore_ids' => [],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
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
