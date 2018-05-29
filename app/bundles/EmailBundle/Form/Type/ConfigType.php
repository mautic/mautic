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

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\SortableListType;
use Mautic\EmailBundle\Model\TransportType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType.
 */
class ConfigType extends AbstractType
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
     * ConfigType constructor.
     *
     * @param TranslatorInterface $translator
     * @param TransportType       $transportType
     */
    public function __construct(TranslatorInterface $translator, TransportType $transportType)
    {
        $this->translator    = $translator;
        $this->transportType = $transportType;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new CleanFormSubscriber(
                [
                    'mailer_from_email'      => 'email',
                    'mailer_return_path'     => 'email',
                    'default_signature_text' => 'html',
                    'unsubscribe_text'       => 'html',
                    'unsubscribe_message'    => 'html',
                    'resubscribe_message'    => 'html',
                    'webview_text'           => 'html',
                    // Encode special chars to keep congruent with Email entity custom headers
                    'mailer_custom_headers'  => 'clean',
                ]
            )
        );

        $builder->add(
            'unsubscribe_text',
            'textarea',
            [
                'label'      => 'mautic.email.config.unsubscribe_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_text.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_text', $options['data']) && !empty($options['data']['unsubscribe_text']))
                    ? $options['data']['unsubscribe_text']
                    : $this->translator->trans(
                        'mautic.email.unsubscribe.text',
                        ['%link%' => '|URL|']
                    ),
            ]
        );

        $builder->add(
            'webview_text',
            'textarea',
            [
                'label'      => 'mautic.email.config.webview_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.webview_text.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('webview_text', $options['data']) && !empty($options['data']['webview_text']))
                    ? $options['data']['webview_text']
                    : $this->translator->trans(
                        'mautic.email.webview.text',
                        ['%link%' => '|URL|']
                    ),
            ]
        );

        $builder->add(
            'unsubscribe_message',
            'textarea',
            [
                'label'      => 'mautic.email.config.unsubscribe_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_message.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_message', $options['data']) && !empty($options['data']['unsubscribe_message']))
                    ? $options['data']['unsubscribe_message']
                    : $this->translator->trans(
                        'mautic.email.unsubscribed.success',
                        [
                            '%resubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'resubscribe_message',
            'textarea',
            [
                'label'      => 'mautic.email.config.resubscribe_message',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.resubscribe_message.tooltip',
                ],
                'required'   => false,
                'data'       => (array_key_exists('resubscribe_message', $options['data']) && !empty($options['data']['resubscribe_message']))
                    ? $options['data']['resubscribe_message']
                    : $this->translator->trans(
                        'mautic.email.resubscribed.success',
                        [
                            '%unsubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'default_signature_text',
            'textarea',
            [
                'label'      => 'mautic.email.config.default_signature_text',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.default_signature_text.tooltip',
                ],
                'required'   => false,
                'data'       => (!empty($options['data']['default_signature_text']))
                    ? $options['data']['default_signature_text']
                    : $this->translator->trans(
                        'mautic.email.default.signature',
                        [
                            '%from_name%' => '|FROM_NAME|',
                        ]
                    ),
            ]
        );

        $builder->add(
            'mailer_from_name',
            'text',
            [
                'label'       => 'mautic.email.config.mailer.from.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.from.name.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton()',
                ],
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
            'text',
            [
                'label'       => 'mautic.email.config.mailer.from.email',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.from.email.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton()',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.email.required',
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
            'mailer_return_path',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.return.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.return.path.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_transport',
            ChoiceType::class,
            [
                'choices'     => $this->getTransportChoices(),
                'label'       => 'mautic.email.config.mailer.transport',
                'required'    => false,
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.config.mailer.transport.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton()',
                ],
                'empty_value' => false,
            ]
        );

        $builder->add(
            'mailer_convert_embed_images',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.mailer.convert.embed.images',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.convert.embed.images.tooltip',
                ],
                'data'       => empty($options['data']['mailer_convert_embed_images']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_append_tracking_pixel',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.mailer.append.tracking.pixel',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.append.tracking.pixel.tooltip',
                ],
                'data'       => empty($options['data']['mailer_append_tracking_pixel']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'disable_trackable_urls',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.mailer.disable.trackable.urls',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.disable.trackable.urls.tooltip',
                ],
                'data'       => empty($options['data']['disable_trackable_urls']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_host',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.host',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getServiceRequiresHost().']}',
                    'tooltip'      => 'mautic.email.config.mailer.host.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_amazon_region',
            'choice',
            [
                'choices'     => [
                    'email-smtp.eu-west-1.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.eu_west_1',
                    'email-smtp.us-east-1.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.us_east_1',
                    'email-smtp.us-west-2.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.eu_west_2',
                ],
                'label'       => 'mautic.email.config.mailer.amazon_host',
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getAmazonService().']}',
                    'tooltip'      => 'mautic.email.config.mailer.amazon_host.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'empty_value' => false,
            ]
        );

        $builder->add(
            'mailer_port',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.port',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getServiceRequiresPort().']}',
                    'tooltip'      => 'mautic.email.config.mailer.port.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $smtpServiceShowConditions = '{"config_emailconfig_mailer_transport":['.$this->transportType->getSmtpService().']}';
        $builder->add(
            'mailer_auth_mode',
            'choice',
            [
                'choices'     => [
                    'plain'    => 'mautic.email.config.mailer_auth_mode.plain',
                    'login'    => 'mautic.email.config.mailer_auth_mode.login',
                    'cram-md5' => 'mautic.email.config.mailer_auth_mode.cram-md5',
                ],
                'label'       => 'mautic.email.config.mailer.auth.mode',
                'label_attr'  => ['class' => 'control-label'],
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.auth.mode.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'empty_value' => 'mautic.email.config.mailer_auth_mode.none',
            ]
        );

        $builder->add(
            'mailer_user',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.user',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{
                        "config_emailconfig_mailer_auth_mode":[
                            "plain",
                            "login",
                            "cram-md5"
                        ], 
                        "config_emailconfig_mailer_transport":['.$this->transportType->getServiceRequiresUser().']
                    }',
                    'data-hide-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getServiceDoNotNeedUser().']}',
                    'tooltip'      => 'mautic.email.config.mailer.user.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                    'autocomplete' => 'off',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_password',
            'password',
            [
                'label'      => 'mautic.email.config.mailer.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'data-show-on' => '{
                        "config_emailconfig_mailer_auth_mode":[
                            "plain",
                            "login",
                            "cram-md5"
                        ], 
                        "config_emailconfig_mailer_transport":['.$this->transportType->getServiceRequiresPassword().']
                    }',
                    'data-hide-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getServiceDoNotNeedPassword().']}',
                    'tooltip'      => 'mautic.email.config.mailer.password.tooltip',
                    'autocomplete' => 'off',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_api_key',
            'password',
            [
                'label'      => 'mautic.email.config.mailer.apikey',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getServiceRequiresApiKey().']}',
                    'tooltip'      => 'mautic.email.config.mailer.apikey.tooltop',
                    'autocomplete' => 'off',
                    'placeholder'  => 'mautic.email.config.mailer.apikey.placeholder',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_encryption',
            'choice',
            [
                'choices'     => [
                    'ssl' => 'mautic.email.config.mailer_encryption.ssl',
                    'tls' => 'mautic.email.config.mailer_encryption.tls',
                ],
                'label'       => 'mautic.email.config.mailer.encryption',
                'required'    => false,
                'attr'        => [
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.encryption.tooltip',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'empty_value' => 'mautic.email.config.mailer_encryption.none',
            ]
        );

        $builder->add(
            'mailer_test_connection_button',
            'standalone_button',
            [
                'label'    => 'mautic.email.config.mailer.transport.test_connection',
                'required' => false,
                'attr'     => [
                    'class'   => 'btn btn-success',
                    'onclick' => 'Mautic.testEmailServerConnection()',
                ],
            ]
        );

        $builder->add(
            'mailer_test_send_button',
            'standalone_button',
            [
                'label'    => 'mautic.email.config.mailer.transport.test_send',
                'required' => false,
                'attr'     => [
                    'class'   => 'btn btn-info',
                    'onclick' => 'Mautic.sendTestEmail()',
                ],
            ]
        );

        $builder->add(
            'mailer_mailjet_sandbox',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.mailer.mailjet.sandbox',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.mailer.mailjet.sandbox',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getMailjetService().']}',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'data'       => empty($options['data']['mailer_mailjet_sandbox']) ? false : true,
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_mailjet_sandbox_default_mail',
            'text',
            [
                'label'       => 'mautic.email.config.mailer.mailjet.sandbox.mail',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'        => 'form-control',
                    'tooltip'      => 'mautic.email.config.mailer.mailjet.sandbox.mail',
                    'data-show-on' => '{"config_emailconfig_mailer_transport":['.$this->transportType->getMailjetService().']}',
                    'data-hide-on' => '{"config_emailconfig_mailer_mailjet_sandbox_0":"checked"}',
                    'onchange'     => 'Mautic.disableSendTestEmailButton()',
                ],
                'constraints' => [
                    new Email(
                        [
                            'message' => 'mautic.core.email.required',
                        ]
                    ),
                ],
                'required'    => false,
            ]
        );

        $spoolConditions = '{"config_emailconfig_mailer_spool_type":["memory"]}';

        $builder->add(
            'mailer_spool_type',
            'choice',
            [
                'choices'     => [
                    'memory' => 'mautic.email.config.mailer_spool_type.memory',
                    'file'   => 'mautic.email.config.mailer_spool_type.file',
                ],
                'label'       => 'mautic.email.config.mailer.spool.type',
                'label_attr'  => ['class' => 'control-label'],
                'required'    => false,
                'attr'        => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.spool.type.tooltip',
                ],
                'empty_value' => false,
            ]
        );

        $builder->add(
            'mailer_custom_headers',
            SortableListType::class,
            [
                'required'        => false,
                'label'           => 'mautic.email.custom_headers',
                'attr'            => [
                    'tooltip'  => 'mautic.email.custom_headers.config.tooltip',
                    'onchange' => 'Mautic.disableSendTestEmailButton()',
                ],
                'option_required' => false,
                'with_labels'     => true,
                'key_value_pairs' => true, // do not store under a `list` key and use label as the key
            ]
        );

        $builder->add(
            'mailer_spool_path',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.spool.path',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.path.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_spool_msg_limit',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.spool.msg.limit',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.msg.limit.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_spool_time_limit',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.spool.time.limit',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.time.limit.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_spool_recover_timeout',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.spool.recover.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.recover.timeout.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'mailer_spool_clear_timeout',
            'text',
            [
                'label'      => 'mautic.email.config.mailer.spool.clear.timeout',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.clear.timeout.tooltip',
                ],
                'required'   => false,
            ]
        );

        $builder->add(
            'monitored_email',
            'monitored_email',
            [
                'label'    => false,
                'data'     => (array_key_exists('monitored_email', $options['data'])) ? $options['data']['monitored_email'] : [],
                'required' => false,
            ]
        );

        $builder->add(
            'mailer_is_owner',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.mailer.is.owner',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.is.owner.tooltip',
                ],
                'data'       => empty($options['data']['mailer_is_owner']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'email_frequency_number',
            'number',
            [
                'precision'  => 0,
                'label'      => 'mautic.lead.list.frequency.number',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );
        $builder->add(
            'email_frequency_time',
            'choice',
            [
                'choices'    => [
                    'DAY'   => 'day',
                    'WEEK'  => 'week',
                    'MONTH' => 'month',
                ],
                'label'      => 'mautic.lead.list.frequency.times',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'multiple'   => false,
                'attr'       => [
                    'class' => 'form-control frequency',
                ],
            ]
        );
        $builder->add(
            'show_contact_segments',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.contact.segments',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.segments.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_segments']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_preferences',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.preference.options',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.preference.options.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_preferences']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_frequency',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.contact.frequency',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.frequency.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_frequency']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_pause_dates',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.contact.pause.dates',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.pause.dates.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_pause_dates']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_categories',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.contact.categories',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.categories.tooltip',
                ],
                'data'       => empty($options['data']['show_contact_categories']) ? false : true,
                'required'   => false,
            ]
        );
        $builder->add(
            'show_contact_preferred_channels',
            'yesno_button_group',
            [
                'label'      => 'mautic.email.config.show.contact.preferred.channels',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.show.contact.preferred.channels',
                ],
                'data'       => empty($options['data']['show_contact_preferred_channels']) ? false : true,
                'required'   => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emailconfig';
    }

    /**
     * @return array
     */
    private function getTransportChoices()
    {
        $choices = $this->transportType->getTransportTypes();

        foreach ($choices as $value => $label) {
            $choices[$value] = $this->translator->trans($label);
        }

        asort($choices, SORT_NATURAL);

        return $choices;
    }
}
