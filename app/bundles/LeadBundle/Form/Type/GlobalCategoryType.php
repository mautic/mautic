<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GlobalCategoryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'model'              => 'category.category',
                'multiple'           => true,
                'ajax_lookup_action' => function (Options $options) {
                    $query = [
                        'for_lookup' => 1,
                    ];

                    return 'lead:getLookupChoiceList&'.http_build_query($query);
                },
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => function (Options $options) {
                    return [
                        'type'    => 'global',
                        'filter'  => '$data',
                        'limit'   => 10,
                        'start'   => 0,
                        'options' => [
                            'is_published' => $options['is_published'],
                            'for_lookup'   => 1,
                        ],
                    ];
                },
                'is_published' => true,
            ]
        );
    }

    public function getParent(): string
    {
        return EntityLookupType::class;
    }
}
