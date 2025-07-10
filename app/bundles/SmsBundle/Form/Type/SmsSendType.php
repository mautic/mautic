<?php

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
class SmsSendType extends AbstractType
{
    public function __construct(
        protected RouterInterface $router,
    ) {
    }

    /**
     * @param FormBuilderInterface<array<mixed>|null> $builder
     * @param array<string, mixed>                    $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'sms',
            SmsListType::class,
            [
                'label'      => 'mautic.sms.send.selectsmss',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.sms.choose.smss',
                    'onchange' => 'Mautic.disabledSmsAction()',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.sms.choosesms.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_sms_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newSmsButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'ri-add-line',
                    ],
                    'label' => 'mautic.sms.send.new.sms',
                ]
            );

            // create button edit sms
            $windowUrlEdit = $this->router->generate(
                'mautic_sms_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'smsId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editSmsButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardSmsUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($options['data']['sms']),
                        'icon'     => 'ri-edit-line',
                    ],
                    'label' => 'mautic.sms.send.edit.sms',
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['update_select']);
    }

    public function getBlockPrefix(): string
    {
        return 'smssend_list';
    }
}
