<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Email;

/**
 * Class ConfigType
 */
class ConfigMonitoredMailboxesType extends AbstractType
{

    /**
     * @var MauticFactory
     */
    private $factory;

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
        $monitoredShowOn = ($options['mailbox'] == 'general') ? '{}'
            : '{"config_emailconfig_monitored_email_'.$options['mailbox'].'_override_settings_1": "checked"}';

        $builder->add(
            'address',
            'text',
            array(
                'label'       => 'mautic.email.config.monitored_email_address',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_address.tooltip',
                    'data-show-on' => $monitoredShowOn
                ),
                'constraints' => array(
                    new Email(
                        array(
                            'message' => 'mautic.core.email.required'
                        )
                    )
                ),
                'required'    => false
            )
        );

        $builder->add(
            'host',
            'text',
            array(
                'label'      => 'mautic.email.config.monitored_email_host',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_host.tooltip',
                    'data-show-on' => $monitoredShowOn
                ),
                'required'   => false
            )
        );

        $builder->add(
            'port',
            'text',
            array(
                'label'      => 'mautic.email.config.monitored_email_port',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_port.tooltip',
                    'data-show-on' => $monitoredShowOn
                ),
                'required'   => false,
                'data'       => (array_key_exists('port', $options['data']))
                    ? $options['data']['port'] : 993
            )
        );

        if (extension_loaded('openssl')) {
            $builder->add(
                'encryption',
                'choice',
                array(
                    'choices'     => array(
                        '/ssl' => 'mautic.email.config.mailer_encryption.ssl',
                        '/ssl/novalidate-cert' => 'mautic.email.config.monitored_email_encryption.ssl_novalidate',
                        '/tls' => 'mautic.email.config.mailer_encryption.tls',
                        '/tls/novalidate-cert' => 'mautic.email.config.monitored_email_encryption.tls_novalidate'
                    ),
                    'label'       => 'mautic.email.config.monitored_email_encryption',
                    'required'    => false,
                    'attr'        => array(
                        'class'        => 'form-control',
                        'data-show-on' => $monitoredShowOn,
                        'tooltip'      => 'mautic.email.config.monitored_email_encryption.tooltip'
                    ),
                    'empty_value' => 'mautic.email.config.mailer_encryption.none',
                    'data'        => (isset($options['data']['encryption'])) ? $options['data']['encryption'] : '/ssl'
                )
            );
        }

        $builder->add(
            'user',
            'text',
            array(
                'label'      => 'mautic.email.config.monitored_email_user',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.monitored_email_user.tooltip',
                    'autocomplete' => 'off',
                    'data-show-on' => $monitoredShowOn
                ),
                'required'   => false
            )
        );

        $builder->add(
            'password',
            'password',
            array(
                'label'      => 'mautic.email.config.monitored_email_password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'tooltip'      => 'mautic.email.config.monitored_email_password.tooltip',
                    'autocomplete' => 'off',
                    'data-show-on' => $monitoredShowOn
                ),
                'required'   => false
            )
        );

        if ($options['mailbox'] != 'general') {
            $builder->add(
                'override_settings',
                'yesno_button_group',
                array(
                    'label'      => 'mautic.email.config.monitored_email_override_settings',
                    'label_attr' => array('class' => 'control-label'),
                    'data'       => (array_key_exists('override_settings', $options['data']) && !empty($options['data']['override_settings'])) ? true : false,
                    'attr'       => array(
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.email.config.monitored_email_override_settings.tooltip'
                    ),
                    'required'   => false
                )
            );

            /** @var \Mautic\EmailBundle\MonitoredEmail\Mailbox $mailbox */
            $mailbox  = $this->factory->getHelper('mailbox');
            $settings = (empty($options['data']['override_settings'])) ? $options['general_settings'] : $options['data'];

            $mailbox->setMailboxSettings($settings);

            // Check for IMAP connection and get a folder list
            $choices = array(
                'INBOX' => 'INBOX',
                'Trash' => 'Trash'
            );

            if ($mailbox->isConfigured()) {
                try {
                    $folders = $mailbox->getListingFolders();
                    $choices = array_combine($folders, $folders);
                } catch (\Exception $e) {}
            }

            $builder->add(
                'folder',
                'choice',
                array(
                    'choices'    => $choices,
                    'label'      => 'mautic.email.config.monitored_email_folder',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array_merge(
                        array(
                            'class'             => 'form-control',
                            'tooltip'           => 'mautic.email.config.monitored_email_folder.tooltip',
                            'data-imap-folders' => $options['mailbox']
                        )
                    ),
                    'data'       => (array_key_exists('folder', $options['data']))
                        ? $options['data']['folder'] : $options['default_folder'],
                    'required'   => false
                )
            );
        }

        $builder->add(
            'test_connection_button',
            'standalone_button',
            array(
                'label'    => 'mautic.email.config.monitored_email.test_connection',
                'required' => false,
                'attr'     => array(
                    'class'   => 'btn btn-success',
                    'onclick' => 'Mautic.testMonitoredEmailServerConnection(\''.$options['mailbox'].'\')'
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('mailbox', 'default_folder', 'general_settings'));
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