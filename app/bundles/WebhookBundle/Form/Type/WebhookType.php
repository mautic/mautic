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
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\WebhookBundle\Form\DataTransformer\EventsToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportType.
 */
class WebhookType extends AbstractType
{
    /**
     * Translator object.
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * WebhookType constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'strict_html']));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'description',
            'textarea',
            [
                'label'    => 'mautic.webhook.form.description',
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'webhookUrl',
            'url',
            [
                'label'      => 'mautic.webhook.form.webhook_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $events = $options['events'];

        $choices = [];
        foreach ($events as $type => $event) {
            $choices[$type] = $event['label'];
        }

        $builder->add(
            'events',
            'choice',
            [
                'choices'    => $choices,
                'multiple'   => true,
                'expanded'   => true,
                'label'      => 'mautic.webhook.form.webhook.events',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => ''],
            ]
        );

        $builder->get('events')->addModelTransformer(new EventsToArrayTransformer($options['data']));

        $builder->add('buttons', 'form_buttons');

        $builder->add(
            'sendTest',
            'button',
            [
                'attr'  => ['class' => 'btn btn-success', 'onclick' => 'Mautic.sendHookTest(this)'],
                'label' => 'mautic.webhook.send.test.payload',
            ]
        );

        //add category
        $builder->add(
            'category',
            'category',
            [
                'bundle' => 'Webhook',
            ]
        );

        $builder->add('isPublished', 'yesno_button_group');

        $builder->add('eventsOrderbyDir', 'choice', [
            'choices' => [
                ''             => 'mautic.core.form.default',
                Criteria::ASC  => 'mautic.webhook.config.event.orderby.chronological',
                Criteria::DESC => 'mautic.webhook.config.event.orderby.reverse.chronological',
            ],
            'label' => 'mautic.webhook.config.event.orderby',
            'attr'  => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.webhook.config.event.orderby.tooltip',
            ],
            'empty_value' => '',
            'required'    => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'Mautic\WebhookBundle\Entity\Webhook',
            ]
        );

        $resolver->setDefined(['events']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'webhook';
    }
}
