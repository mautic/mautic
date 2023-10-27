<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ScheduleSendType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

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
                'constraints' => [
                    new Callback(function ($value, ExecutionContextInterface $context) {
                        $data            = $context->getRoot()->getData() ?? [];
                        $continueSending = $data['continueSending'] ?? false;
                        $publishUp       = $data['publishUp'] ?? null;
                        if ($continueSending && $value && $publishUp && $value <= $publishUp) {
                            $context->buildViolation($this->translator->trans('mautic.form.date_time_range.invalid_range', [], 'validators'))
                                ->addViolation();
                        }
                    }),
                ],
            ]
        );

        if (empty(array_filter($options['data'] ?? []))) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'save_text'  => 'mautic.email.send.schedule',
                    'save_icon'  => 'fa fa-clock-o',
                    'apply_text' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'save_text'   => 'mautic.email.send.schedule.update',
                    'save_icon'   => 'fa fa-clock-o',
                    'apply_text'  => 'mautic.email.send.schedule.cancel',
                    'apply_icon'  => 'fa fa-ban',
                ]
            );
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $data = $event->getData();

            if (isset($data['buttons']['apply'])) {
                $options                = $form->get('publishUp')->getConfig()->getOptions();
                $options['constraints'] = [];
                $form->add('publishUp', DateTimeType::class, $options);
            }
        });
    }
}
