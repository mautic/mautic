<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\SmsBundle\Validator\ScheduleDateRange;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class ScheduleSendType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('publishUp', DateTimeType::class, [
            'widget' => 'single_text',
            'label'  => 'mautic.sms.send.datetime.start',
            'attr'   => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'      => 'yyyy-MM-dd HH:mm',
            'required'    => false,
            'html5'       => false,
            'constraints' => new NotBlank(message: 'mautic.core.value.required'),
        ]);

        $builder->add('continueSending', YesNoButtonGroupType::class, [
            'label'    => 'mautic.sms.send.continue',
            'required' => false,
            'attr'     => [
                'tooltip' => 'mautic.sms.send.continue.tooltip',
            ],
            'data' => $options['data']['continueSending'] ?? false,
        ]);

        $builder->add('publishDown', DateTimeType::class, [
            'widget' => 'single_text',
            'label'  => 'mautic.sms.send.datetime.end',
            'attr'   => [
                'class'        => 'form-control',
                'data-toggle'  => 'datetime',
                'data-show-on' => '{"schedule_send_continueSending_1":"checked"}',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'html5'    => false,
            'required' => false,
        ]);

        $buttonOptions = [
            'save_text'  => 'mautic.sms.send.schedule',
            'save_icon'  => null,
            'apply_text' => false,
        ];

        if ($options['is_scheduled']) {
            $buttonOptions = [
                'save_text'   => 'mautic.sms.send.schedule.update',
                'save_icon'   => null,
                'save_class'  => 'btn btn-primary',
                'apply_text'  => 'mautic.sms.send.schedule.cancel',
                'apply_icon'  => null,
                'apply_class' => 'btn btn-secondary btn-cancel',
                'cancel_text' => 'mautic.core.close',
                'cancel_icon' => null,
            ];
        }

        $builder->add('buttons', FormButtonsType::class, $buttonOptions);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $data = $event->getData();
            if (!is_array($data)) {
                return;
            }

            if (isset($data['continueSending']) && !$data['continueSending']) {
                $data['publishDown'] = null;
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'is_scheduled' => false,
            'constraints'  => [new ScheduleDateRange()],
        ]);
        $resolver->setAllowedTypes('is_scheduled', 'bool');
    }
}
