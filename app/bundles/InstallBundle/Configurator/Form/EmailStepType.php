<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmailStepType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'mailer_from_name',
            TextType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.install.form.email.from_name',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_from_email',
            EmailType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'preaddon'    => 'fa fa-envelope',
                    'placeholder' => 'mautic.install.form.email.from_address',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'mailer_transport',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.email.config.mailer_transport.mandrill' => 'mautic.transport.mandrill',
                    'mautic.email.config.mailer_transport.mailjet'  => 'mautic.transport.mailjet',
                    'mautic.email.config.mailer_transport.sendgrid' => 'mautic.transport.sendgrid',
                    'mautic.email.config.mailer_transport.amazon'   => 'mautic.transport.amazon',
                    'mautic.email.config.mailer_transport.postmark' => 'mautic.transport.postmark',
                    'mautic.email.config.mailer_transport.gmail'    => 'gmail',
                    'mautic.email.config.mailer_transport.smtp'     => 'smtp',
                    'mautic.email.config.mailer_transport.sendmail' => 'sendmail',
                ],
                'label'             => 'mautic.install.form.email.transport',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'attr'              => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.install.form.email.transport_descr',
                    'onchange' => 'MauticInstaller.toggleTransportDetails(this.value);',
                ],
            ]
        );

        $builder->add(
            'mailer_host',
            TextType::class,
            [
                'label'      => 'mautic.install.form.email.mailer_host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'mailer_port',
            TextType::class,
            [
                'label'      => 'mautic.install.form.email.mailer_port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'mailer_user',
            TextType::class,
            [
                'label'      => 'mautic.core.username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'mailer_password',
            PasswordType::class,
            [
                'label'      => 'mautic.core.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-lock',
                ],
            ]
        );

        $builder->add(
            'mailer_encryption',
            ButtonGroupType::class,
            [
                'choices' => [
                    'mautic.email.config.mailer_encryption.tls' => 'tls',
                    'mautic.email.config.mailer_encryption.ssl' => 'ssl',
                ],
                'label'       => 'mautic.install.form.email.encryption',
                'expanded'    => true,
                'placeholder' => 'mautic.install.form.none',
            ]
        );

        $builder->add(
            'mailer_auth_mode',
            ChoiceType::class,
            [
                'choices' => [
                    'mautic.email.config.mailer_auth_mode.plain'    => 'plain',
                    'mautic.email.config.mailer_auth_mode.login'    => 'login',
                    'mautic.email.config.mailer_auth_mode.cram-md5' => 'cram-md5',
                ],
                'label'       => 'mautic.install.form.email.auth_mode',
                'label_attr'  => ['class' => 'control-label'],
                'placeholder' => 'mautic.install.form.none',
                'attr'        => [
                    'class'    => 'form-control',
                    'onchange' => 'MauticInstaller.toggleAuthDetails(this.value);',
                ],
            ]
        );

        $builder->add(
            'mailer_spool_type',
            ButtonGroupType::class,
            [
                'choices' => [
                    'mautic.email.config.mailer_spool_type.memory' => 'memory',
                    'mautic.email.config.mailer_spool_type.file'   => 'file',
                ],
                'label'       => 'mautic.install.form.email.spool_type',
                'expanded'    => true,
                'placeholder' => false,
            ]
        );

        $builder->add('mailer_spool_path', HiddenType::class);

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'pre_extra_buttons' => [
                    [
                        'name'  => 'next',
                        'label' => 'mautic.install.next.step',
                        'type'  => 'submit',
                        'attr'  => [
                            'class'   => 'btn btn-success pull-right btn-next',
                            'icon'    => 'fa fa-arrow-circle-right',
                            'onclick' => 'MauticInstaller.showWaitMessage(event);',
                        ],
                    ],
                ],
                'apply_text'  => '',
                'save_text'   => '',
                'cancel_text' => '',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'install_email_step';
    }
}
