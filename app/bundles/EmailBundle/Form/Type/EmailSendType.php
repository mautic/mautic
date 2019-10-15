<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\ChannelBundle\Entity\MessageQueue;
use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EmailSendType.
 */
class EmailSendType extends AbstractType
{
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            'email_list',
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
                'button_group',
                [
                    'choices' => [
                        'transactional' => 'mautic.email.send.emailtype.transactional',
                        'marketing'     => 'mautic.email.send.emailtype.marketing',
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
            $windowUrl = $this->factory->getRouter()->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select'],
                ]
            );

            $builder->add(
                'newEmailButton',
                'button',
                [
                    'attr' => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon' => 'fa fa-plus',
                    ],
                    'label' => 'mautic.email.send.new.email',
                ]
            );

            // create button edit email
            $windowUrlEdit = $this->factory->getRouter()->generate(
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
                'button',
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlEdit.'","origin":"#'.$options['update_select'].'"}))',
                        'disabled' => !isset($options['data']['email']),
                        'icon'     => 'fa fa-edit',
                    ],
                    'label' => 'mautic.email.send.edit.email',
                ]
            );

            // create button preview email
            $windowUrlPreview = $this->factory->getRouter()->generate('mautic_email_preview', ['objectId' => 'emailId']);

            $builder->add(
                'previewEmailButton',
                'button',
                [
                    'attr' => [
                        'class'    => 'btn btn-primary btn-nospin',
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
                    'choice',
                    [
                        'choices' => [
                            MessageQueue::PRIORITY_NORMAL => 'mautic.channel.message.send.priority.normal',
                            MessageQueue::PRIORITY_HIGH   => 'mautic.channel.message.send.priority.high',
                        ],
                        'label'    => 'mautic.channel.message.send.priority',
                        'required' => false,
                        'attr'     => [
                            'class'        => 'form-control',
                            'tooltip'      => 'mautic.channel.message.send.priority.tooltip',
                            'data-show-on' => '{"campaignevent_properties_email_type_1":"checked"}',
                        ],
                        'data'        => $data,
                        'empty_value' => false,
                    ]
                );

                $data = (!isset($options['data']['attempts'])) ? 3 : (int) $options['data']['attempts'];
                $builder->add(
                    'attempts',
                    'number',
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

    /**
     * @param OptionsResolver $resolver
     */
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
    public function getName()
    {
        return 'emailsend_list';
    }
}
