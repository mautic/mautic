<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\StandAloneButtonType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

class ConfigMonitoredMailboxesType extends AbstractType
{
    public function __construct(
        private Mailbox $imapHelper
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $monitoredShowOn = ('general' == $options['mailbox']) ? '{}'
            : '{"config_emailconfig_monitored_email_'.$options['mailbox'].'_override_settings_1": "checked"}';

        $builder->add(
            'address',
            TextType::class,
            [
                'label'      => 'mautic.email.config.monitored_email_address',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_address.tooltip',
                    'data-show-on' => $monitoredShowOn,
                ],
                'constraints' => [
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                        ]
                    ),
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'host',
            TextType::class,
            [
                'label'      => 'mautic.email.config.monitored_email_host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_host.tooltip',
                    'data-show-on' => $monitoredShowOn,
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'port',
            TextType::class,
            [
                'label'      => 'mautic.email.config.monitored_email_port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_port.tooltip',
                    'data-show-on' => $monitoredShowOn,
                ],
                'required' => false,
                'data'     => (array_key_exists('port', $options['data']))
                    ? $options['data']['port'] : 993,
            ]
        );

        if (extension_loaded('openssl')) {
            $builder->add(
                'encryption',
                ChoiceType::class,
                [
                    'choices'           => [
                        'mautic.email.config.mailer_encryption.ssl'                     => '/ssl',
                        'mautic.email.config.monitored_email_encryption.ssl_novalidate' => '/ssl/novalidate-cert',
                        'mautic.email.config.mailer_encryption.tls'                     => '/tls',
                        'mautic.email.config.monitored_email_encryption.tls_novalidate' => '/tls/novalidate-cert',
                    ],
                    'label'    => 'mautic.email.config.monitored_email_encryption',
                    'required' => false,
                    'attr'     => [
                        'class'        => 'form-control',
                        'data-show-on' => $monitoredShowOn,
                        'tooltip'      => 'mautic.email.config.monitored_email_encryption.tooltip',
                    ],
                    'placeholder' => 'mautic.email.config.mailer_encryption.none',
                    'data'        => $options['data']['encryption'] ?? '/ssl',
                ]
            );
        }

        $builder->add(
            'user',
            TextType::class,
            [
                'label'      => 'mautic.email.config.monitored_email_user',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_user.tooltip',
                    'autocomplete' => 'off',
                    'data-show-on' => $monitoredShowOn,
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label'      => 'mautic.email.config.monitored_email_password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'tooltip'      => 'mautic.email.config.monitored_email_password.tooltip',
                    'autocomplete' => 'off',
                    'data-show-on' => $monitoredShowOn,
                ],
                'required' => false,
            ]
        );

        if ('general' != $options['mailbox']) {
            $builder->add(
                'override_settings',
                YesNoButtonGroupType::class,
                [
                    'label'      => 'mautic.email.config.monitored_email_override_settings',
                    'label_attr' => ['class' => 'control-label'],
                    'data'       => (array_key_exists('override_settings', $options['data']) && !empty($options['data']['override_settings'])) ? true : false,
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.email.config.monitored_email_override_settings.tooltip',
                    ],
                    'required' => false,
                ]
            );

            $settings = (empty($options['data']['override_settings'])) ? $options['general_settings'] : $options['data'];

            $this->imapHelper->setMailboxSettings($settings);

            // Check for IMAP connection and get a folder list
            $choices = [
                'INBOX' => 'INBOX',
                'Trash' => 'Trash',
            ];

            if ($this->imapHelper->isConfigured()) {
                try {
                    $folders = $this->imapHelper->getListingFolders();
                    $choices = array_combine($folders, $folders);
                } catch (\Exception) {
                    // If the connection failed - add back the selected folder just in case it's a temporary connection issue
                    if (!empty($options['data']['folder'])) {
                        $choices[$options['data']['folder']] = $options['data']['folder'];
                    }
                }
            }

            $builder->add(
                'folder',
                ChoiceType::class,
                [
                    'choices'           => $choices,
                    'label'             => 'mautic.email.config.monitored_email_folder',
                    'label_attr'        => ['class' => 'control-label'],
                    'attr'              => array_merge(
                        [
                            'class'             => 'form-control',
                            'tooltip'           => 'mautic.email.config.monitored_email_folder.tooltip',
                            'data-imap-folders' => $options['mailbox'],
                        ]
                    ),
                    'data' => (array_key_exists('folder', $options['data']))
                        ? $options['data']['folder'] : $options['default_folder'],
                    'required' => false,
                ]
            );
        }

        $builder->add(
            'test_connection_button',
            StandAloneButtonType::class,
            [
                'label'    => 'mautic.email.config.monitored_email.test_connection',
                'required' => false,
                'attr'     => [
                    'class'   => 'btn btn-success',
                    'onclick' => 'Mautic.testMonitoredEmailServerConnection(\''.$options['mailbox'].'\')',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['mailbox', 'default_folder', 'general_settings']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['mailbox'] = $options['mailbox'];
    }

    public function getBlockPrefix()
    {
        return 'monitored_mailboxes';
    }
}
