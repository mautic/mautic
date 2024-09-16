<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class EmailListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'required'    => false,
                'modal_route' => 'mautic_email_action',
                // Email form UI too complicated for a modal so force a popup
                'force_popup'        => true,
                'model'              => 'email',
                'multiple'           => true,
                'ajax_lookup_action' => function (Options $options): string {
                    $query = [
                        'email_type'     => $options['email_type'],
                        'top_level'      => $options['top_level'],
                        'variant_parent' => $options['variant_parent'],
                        'ignore_ids'     => $options['ignore_ids'],
                    ];

                    return 'email:getLookupChoiceList&'.http_build_query($query);
                },
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'    => 'email',
                    'filter'  => '$data',
                    'limit'   => 0,
                    'start'   => 0,
                    'options' => [
                        'email_type'     => $options['email_type'],
                        'top_level'      => $options['top_level'],
                        'variant_parent' => $options['variant_parent'],
                        'ignore_ids'     => $options['ignore_ids'],
                    ],
                ],
                // 'modal_route_parameters' => 'template'
                'email_type'     => 'template',
                'top_level'      => 'variant',
                'variant_parent' => null,
                'ignore_ids'     => [],

                'email' => null,
            ]
        );
    }

    /**
     * @return string
     */
    public function getParent(): ?string
    {
        return EntityLookupType::class;
    }
}
