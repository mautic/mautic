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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('queue_mode', 'choice', [
            'choices' => [
                'immediate_process' => 'mautic.webhook.config.immediate_process',
                'command_process'   => 'mautic.webhook.config.cron_process',
            ],
            'label' => 'mautic.webhook.config.form.queue.mode',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.form.queue.mode.tooltip',
            ],
            'empty_value' => false,
            'constraints' => [
                new NotBlank(
                    [
                        'message' => 'mautic.core.value.required',
                    ]
                ),
            ],
        ]);

        $builder->add('events_orderby_dir', 'choice', [
            'choices' => [
                Criteria::ASC  => 'mautic.webhook.config.event.orderby.chronological',
                Criteria::DESC => 'mautic.webhook.config.event.orderby.reverse.chronological',
            ],
            'label' => 'mautic.webhook.config.event.orderby',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.event.orderby.tooltip',
            ],
            'required' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'webhookconfig';
    }
}
