<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailSendType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            EmailListType::class,
            [
                'label'      => 'mautic.email.send.selectemails',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.choose.emails_descr',
                    'onchange' => 'Mautic.disabledEmailAction(window, this)',
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.email.chooseemail.notblank']
                    ),
                ],
            ]
        );

        if (!empty($options['with_email_types'])) {
            $builder->add(
                'email_type',
                ButtonGroupType::class,
                [
                    'choices'           => [
                        'mautic.email.send.emailtype.transactional' => 'transactional',
                        'mautic.email.send.emailtype.marketing'     => 'marketing',
                    ],
                    'label'      => 'mautic.email.send.emailtype',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control email-type',
                        'tooltip' => 'mautic.email.send.emailtype.tooltip',
                    ],
                    'data' => (!isset($options['data']['email_type'])) ? 'transactional' : $options['data']['email_type'],
                ]
            );
        }

        if (!empty($options['update_select'])) {
            $windowUrl = $this->router->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'   => 'btn btn-default btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                            "windowUrl": "'.$windowUrl.'"
                        })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.email.send.new.email',
                ]
            );

            // create button edit email
            $windowUrlEdit = $this->router->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'emailId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'editEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-default btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlEdit.'","origin":"#'.$options['update_select'].'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-edit',
                    ],
                    'label' => 'mautic.email.send.edit.email',
                ]
            );

            // create button preview email
            $windowUrlPreview = $this->router->generate('mautic_email_preview', ['objectId' => 'emailId']);

            $builder->add(
                'previewEmailButton',
                ButtonType::class,
                [
                    'attr' => [
                        'class'    => 'btn btn-default btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlPreview.'","origin":"#'.$options['update_select'].'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-external-link',
                    ],
                    'label' => 'mautic.email.send.preview.email',
                ]
            );
            if (!empty($options['with_email_types'])) {
                $data = (!isset($options['data']['priority'])) ? 2 : (int) $options['data']['priority'];
                $builder->add(
                    'priority',
                    ChoiceType::class,
                    [
                        'choices'           => [
                            'mautic.channel.message.send.priority.normal' => MessageQueue::PRIORITY_NORMAL,
                            'mautic.channel.message.send.priority.high'   => MessageQueue::PRIORITY_HIGH,
                        ],
                        'label'    => 'mautic.channel.message.send.priority',
                        'required' => false,
                        'attr'     => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.channel.message.send.priority.tooltip',
                            'data-show-on' => '{"campaignevent_properties_email_type_1":"checked"}',
                        ],
                        'data'        => $data,
                        'placeholder' => false,
                    ]
                );

                $data = (!isset($options['data']['attempts'])) ? 3 : (int) $options['data']['attempts'];
                $builder->add(
                    'attempts',
                    NumberType::class,
                    [
                        'label' => 'mautic.channel.message.send.attempts',
                        'attr'  => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.channel.message.send.attempts.tooltip',
                            'data-show-on' => '{"campaignevent_properties_email_type_1":"checked"}',
                        ],
                        'data'       => $data,
                        'empty_data' => 0,
                        'required'   => false,
                    ]
                );
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'with_email_types' => false,
            ]
        );

        $resolver->setDefined(['update_select', 'with_email_types']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'emailsend_list';
    }
}
