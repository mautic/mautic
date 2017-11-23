<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Class ConfigType.
 */
class ConfigMonitoredMailboxesType extends AbstractType
{
    /**
     * @var Mailbox
     */
    private $imapHelper;

    /**
     * ConfigMonitoredMailboxesType constructor.
     *
     * @param Mailbox $imapHelper
     */
    public function __construct(Mailbox $imapHelper)
    {
        $this->imapHelper = $imapHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $monitoredShowOn = ($options['mailbox'] == 'general') ? '{}'
            : '{"config_emailconfig_monitored_email_'.$options['mailbox'].'_override_settings_1": "checked"}';

        $builder->add(
            'address',
            'text',
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
            'text',
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
            'text',
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
                'choice',
                [
                    'choices' => [
                        '/ssl'                 => 'mautic.email.config.mailer_encryption.ssl',
                        '/ssl/novalidate-cert' => 'mautic.email.config.monitored_email_encryption.ssl_novalidate',
                        '/tls'                 => 'mautic.email.config.mailer_encryption.tls',
                        '/tls/novalidate-cert' => 'mautic.email.config.monitored_email_encryption.tls_novalidate',
                    ],
                    'label'    => 'mautic.email.config.monitored_email_encryption',
                    'required' => false,
                    'attr'     => [
                        'class'        => 'form-control',
                        'data-show-on' => $monitoredShowOn,
                        'tooltip'      => 'mautic.email.config.monitored_email_encryption.tooltip',
                    ],
                    'empty_value' => 'mautic.email.config.mailer_encryption.none',
                    'data'        => (isset($options['data']['encryption'])) ? $options['data']['encryption'] : '/ssl',
                ]
            );
        }

        $builder->add(
            'user',
            'text',
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
            'password',
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

        if ($options['mailbox'] != 'general') {
            $builder->add(
                'override_settings',
                'yesno_button_group',
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
                } catch (\Exception $e) {
                    // If the connection failed - add back the selected folder just in case it's a temporary connection issue
                    if (!empty($options['data']['folder'])) {
                        $choices[$options['data']['folder']] = $options['data']['folder'];
                    }
                }
            }

            $builder->add(
                'folder',
                'choice',
                [
                    'choices'    => $choices,
                    'label'      => 'mautic.email.config.monitored_email_folder',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => array_merge(
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
            'standalone_button',
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['mailbox', 'default_folder', 'general_settings']);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['mailbox'] = $options['mailbox'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'monitored_mailboxes';
    }
}
