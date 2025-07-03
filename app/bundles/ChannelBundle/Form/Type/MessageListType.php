<?php

namespace Mautic\ChannelBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\EntityLookupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class MessageListType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'required'           => false,
                'modal_route'        => 'mautic_message_action',
                'model'              => 'channel.message',
                'multiple'           => true,
                'ajax_lookup_action' => function (Options $options): string {
                    $query = [
                        'is_published' => $options['is_published'],
                    ];

                    return 'channel:getLookupChoiceList&'.http_build_query($query);
                },
                'model_lookup_method' => 'getLookupResults',
                'lookup_arguments'    => fn (Options $options): array => [
                    'type'    => 'channel.message',
                    'filter'  => '$data',
                    'limit'   => 0,
                    'start'   => 0,
                    'options' => [
                        'is_published' => $options['is_published'],
                    ],
                ],
                'is_published' => true,
            ]
        );
    }

    public function getParent(): ?string
    {
        return EntityLookupType::class;
    }
}
