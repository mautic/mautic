<?php

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
class SmsListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'modal_route'         => 'mautic_sms_action',
                'modal_header'        => 'mautic.sms.header.new',
                'model'               => 'sms',
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'    => SmsType::class,
                    'filter'  => '$data',
                    'limit'   => 0,
                    'start'   => 0,
                    'options' => [
                        'sms_type' => $options['sms_type'],
                    ],
                ],
                'ajax_lookup_action' => function (Options $options): string {
                    $query = [
                        'sms_type' => $options['sms_type'],
                    ];

                    return 'sms:getLookupChoiceList&'.http_build_query($query);
                },
                'multiple' => true,
                'required' => false,
                'sms_type' => 'template',
            ]
        );
    }

    public function getParent(): ?string
    {
        return EntityLookupType::class;
    }
}
