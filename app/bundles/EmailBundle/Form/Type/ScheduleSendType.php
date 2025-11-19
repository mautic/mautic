<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\Validator\ScheduleDateRange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<ScheduleSendType>
 */
final class ScheduleSendType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'publishUp',
            DateTimeType::class,
            [
                'widget' => 'single_text',
                'label'  => 'mautic.email.send.datetime.start',
                'attr'   => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'      => 'yyyy-MM-dd HH:mm',
                'required'    => false,
                'html5'       => false,
                'constraints' => new NotBlank(
                    [
                        'message' => 'mautic.core.value.required',
                    ]
                ),
            ]
        );

        $builder->add(
            'continueSending',
            YesNoButtonGroupType::class,
            [
                'label'    => 'mautic.email.send.continue',
                'required' => false,
                'attr'     => [
                    'tooltip' => 'mautic.email.send.continue.tooltip',
                ],
                'data'     => $options['data']['continueSending'] ?? false,
            ]
        );

        $builder->add(
            'publishDown',
            DateTimeType::class,
            [
                'widget' => 'single_text',
                'label'  => 'mautic.email.send.datetime.end',
                'attr'   => [
                    'class'            => 'form-control',
                    'data-toggle'      => 'datetime',
                    'data-show-on'     => '{"schedule_send_continueSending_1":"checked"}',
                ],
                'format'      => 'yyyy-MM-dd HH:mm',
                'html5'       => false,
                'required'    => false,
            ]
        );

        if (empty(array_filter($options['data'] ?? []))) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'save_text'  => 'mautic.email.send.schedule',
                    'save_icon'  => null,
                    'apply_text' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'save_text'   => 'mautic.email.send.schedule.update',
                    'save_icon'   => null,
                    'save_class'  => 'btn btn-primary',
                    'apply_text'  => 'mautic.email.send.schedule.cancel',
                    'apply_icon'  => null,
                    'apply_class' => 'btn btn-secondary btn-cancel',
                    'cancel_text' => 'mautic.core.close',
                    'cancel_icon' => null,
                ]
            );
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();

            // Reset publishDown date if continueSending is disabled
            // This ensures that when users disable "continue sending",
            // any previously set end date is cleared to avoid confusion
            if (isset($data['continueSending']) && !$data['continueSending']) {
                $data['publishDown'] = null;
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'constraints' => [
                new ScheduleDateRange(),
            ],
        ]);
    }
}
