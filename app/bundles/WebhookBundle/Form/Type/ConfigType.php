<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Form\Type;

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
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
                'mautic.webhook.config.event.orderby.chronological'         => Criteria::ASC,
                'mautic.webhook.config.event.orderby.reverse.chronological' => Criteria::DESC,
            ],
            'label' => 'mautic.webhook.config.event.orderby',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.event.orderby.tooltip',
            ],
            'required'          => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'webhookconfig';
    }
}
