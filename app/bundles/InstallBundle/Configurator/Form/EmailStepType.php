<?php

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\EmailBundle\Model\TransportType;
use Mautic\MessengerBundle\Model\MessengerTransportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailStepType extends AbstractType
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var TransportType
     */
    private $transportType;

    /**
     * @var MessengerTransportType
     */
    private $messengerTransportType;

    public function __construct(TranslatorInterface $translator, TransportType $transportType, MessengerTransportType $messengerTransportType)
    {
        $this->translator             = $translator;
        $this->transportType          = $transportType;
        $this->messengerTransportType = $messengerTransportType;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'mailer_from_name',
            TextType::class,
            [
                'label'       => false,
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
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
                'label'       => false,
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
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
                'choices'           => $this->getTransportChoices(),
                'label'             => 'mautic.install.form.email.transport',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.transport.tooltip',
                ],
                'placeholder' => false,
            ]
        );

        $builder->add(
            'mailer_host',
            TextType::class,
            [
                'label'      => 'mautic.install.form.email.mailer_host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"install_email_step_mailer_transport":['.$this->transportType->getServiceRequiresHost().']}',
                    'tooltip'      => 'mautic.email.config.mailer.host.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_port',
            TextType::class,
            [
                'label'      => 'mautic.install.form.email.mailer_port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"install_email_step_mailer_transport":['.$this->transportType->getServiceRequiresPort().']}',
                    'tooltip'      => 'mautic.email.config.mailer.port.tooltip',
                ],
                'required'   => false,
            ]
        );

        $smtpServiceShowConditions = '{"install_email_step_mailer_transport":['.$this->transportType->getSmtpService().']}';
        $builder->add(
            'mailer_auth_mode',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.email.config.mailer_auth_mode.plain'    => 'plain',
                    'mautic.email.config.mailer_auth_mode.login'    => 'login',
                    'mautic.email.config.mailer_auth_mode.cram-md5' => 'cram-md5',
                ],
                'label'       => 'mautic.install.form.email.auth_mode',
                'label_attr'  => ['class' => 'control-label'],
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.auth.mode.tooltip',
                ],
                'placeholder' => 'mautic.email.config.mailer_auth_mode.none',
            ]
        );

        $builder->add(
            'mailer_user',
            TextType::class,
            [
                'label'      => 'mautic.core.username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{
                        "install_email_step_mailer_auth_mode":[
                            "plain",
                            "login",
                            "cram-md5"
                        ],
                        "install_email_step_mailer_transport":['.$this->transportType->getServiceRequiresUser().']
                    }',
                    'data-hide-on' => '{"install_email_step_mailer_transport":['.$this->transportType->getServiceDoNotNeedUser().']}',
                    'tooltip'      => 'mautic.email.config.mailer.user.tooltip',
                    'autocomplete' => 'off',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_password',
            PasswordType::class,
            [
                'label'      => 'mautic.core.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'data-show-on' => '{
                        "install_email_step_mailer_auth_mode":[
                            "plain",
                            "login",
                            "cram-md5"
                        ],
                        "install_email_step_mailer_transport":['.$this->transportType->getServiceRequiresPassword().']
                    }',
                    'data-hide-on' => '{"install_email_step_mailer_transport":['.$this->transportType->getServiceDoNotNeedPassword().']}',
                    'tooltip'      => 'mautic.email.config.mailer.password.tooltip',
                    'autocomplete' => 'off',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_encryption',
            ChoiceType::class,
            [
                'choices'           => [
                    'mautic.email.config.mailer_encryption.ssl' => 'ssl',
                    'mautic.email.config.mailer_encryption.tls' => 'tls',
                ],
                'label'       => 'mautic.install.form.email.encryption',
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.encryption.tooltip',
                ],
                'placeholder' => 'mautic.email.config.mailer_encryption.none',
            ]
        );

        $builder->add('messenger_type', HiddenType::class, [
            'data'   => 'async',
        ]);

        $builder->add(
            'messenger_transport',
            ChoiceType::class,
            [
                'choices'           => $this->getMessengerTransportChoices(),
                'label'             => 'mautic.install.form.email.messenger',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.messenger.tooltip',
                ],
                'placeholder' => false,
            ]
        );

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
                'apply_text'        => '',
                'save_text'         => '',
                'cancel_text'       => '',
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

    /**
     * @return array
     */
    private function getTransportChoices()
    {
        $choices    = [];
        $transports = $this->transportType->getTransportTypes();

        foreach ($transports as $value => $label) {
            $choices[$this->translator->trans($label)] = $value;
        }

        ksort($choices, SORT_NATURAL);

        return $choices;
    }

    /**
     * @return array<string>
     */
    private function getMessengerTransportChoices()
    {
        $choices    = [];
        $transports = $this->messengerTransportType->getTransportTypes();

        foreach ($transports as $value => $label) {
            $choices[$this->translator->trans($label)] = $value;
        }

        ksort($choices, SORT_NATURAL);

        return $choices;
    }
}
