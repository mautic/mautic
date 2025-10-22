<?php

namespace Mautic\WebhookBundle\Form\Type;

use Doctrine\Common\Collections\Order;
use Mautic\CoreBundle\Form\DataTransformer\ArrayLinebreakTransformer;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queue_mode', ChoiceType::class, [
            'choices' => [
                'mautic.webhook.config.immediate_process' => 'immediate_process',
                'mautic.webhook.config.cron_process'      => 'command_process',
            ],
            'label' => 'mautic.webhook.config.form.queue.mode',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.form.queue.mode.tooltip',
            ],
            'placeholder' => false,
            'constraints' => [
                new NotBlank(
                    [
                        'message' => 'mautic.core.value.required',
                    ]
                ),
            ],
        ]);

        $builder->add('events_orderby_dir', ChoiceType::class, [
            'choices' => [
                'mautic.webhook.config.event.orderby.chronological'         => Order::Ascending->value,
                'mautic.webhook.config.event.orderby.reverse.chronological' => Order::Descending->value,
            ],
            'label' => 'mautic.webhook.config.event.orderby',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.event.orderby.tooltip',
            ],
            'required'          => false,
        ]);

        $builder->add(
            'webhook_email_details',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.webhook.config.email.details',
                'data'  => (bool) ($options['data']['webhook_email_details'] ?? null),
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.webhook.config.email.details.tooltip',
                ],
            ]
        );

        $builder->add(
            $builder->create(
                'webhook_allowed_private_addresses',
                TextareaType::class,
                [
                    'label'      => 'mautic.webhook.config.allowed_private_addresses',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.webhook.config.allowed_private_addresses.tooltip',
                        'rows'    => 8,
                    ],
                    'required' => false,
                ]
            )->addViewTransformer(new ArrayLinebreakTransformer())
        );
    }

    public function getBlockPrefix(): string
    {
        return 'webhookconfig';
    }
}
