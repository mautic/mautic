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
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ConfigType
 */
class ConfigType extends AbstractType
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
        $builder->add(
            'unsubscribe_text',
            'textarea',
            array(
                'label'      => 'mautic.email.config.unsubscribe_text',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_text.tooltip'
                ),
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_text', $options['data']) && !empty($options['data']['unsubscribe_text']))
                    ? $options['data']['unsubscribe_text']
                    : $this->factory->getTranslator()->trans(
                        'mautic.email.unsubscribe.text',
                        array('%link%' => '|URL|')
                    )
            )
        );

        $builder->add(
            'webview_text',
            'textarea',
            array(
                'label'      => 'mautic.email.config.webview_text',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.webview_text.tooltip'
                ),
                'required'   => false,
                'data'       => (array_key_exists('webview_text', $options['data']) && !empty($options['data']['webview_text']))
                    ? $options['data']['webview_text']
                    : $this->factory->getTranslator()->trans(
                        'mautic.email.webview.text',
                        array('%link%' => '|URL|')
                    )
            )
        );

        $builder->add(
            'unsubscribe_message',
            'textarea',
            array(
                'label'      => 'mautic.email.config.unsubscribe_message',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.unsubscribe_message.tooltip'
                ),
                'required'   => false,
                'data'       => (array_key_exists('unsubscribe_message', $options['data']) && !empty($options['data']['unsubscribe_message']))
                    ? $options['data']['unsubscribe_message']
                    : $this->factory->getTranslator()->trans(
                        'mautic.email.unsubscribed.success',
                        array(
                            '%resubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|'
                        )
                    )
            )
        );

        $builder->add(
            'resubscribe_message',
            'textarea',
            array(
                'label'      => 'mautic.email.config.resubscribe_message',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.resubscribe_message.tooltip'
                ),
                'required'   => false,
                'data'       => (array_key_exists('resubscribe_message', $options['data']) && !empty($options['data']['resubscribe_message']))
                    ? $options['data']['resubscribe_message']
                    : $this->factory->getTranslator()->trans(
                        'mautic.email.resubscribed.success',
                        array(
                            '%unsubscribeUrl%' => '|URL|',
                            '%email%'          => '|EMAIL|'
                        )
                    )
            )
        );

        $builder->add(
            'default_signature_text',
            'textarea',
            array(
                'label'      => 'mautic.email.config.default_signature_text',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.default_signature_text.tooltip'
                ),
                'required'   => false,
                'data'       => (!empty($options['data']['default_signature_text']))
                    ? $options['data']['default_signature_text']
                    : $this->factory->getTranslator()->trans(
                        'mautic.email.default.signature',
                        array(
                            '%from_name%' => '|FROM_NAME|'
                        )
                    )
            )
        );

        $builder->add(
            'mailer_from_name',
            'text',
            array(
                'label'       => 'mautic.email.config.mailer.from.name',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.from.name.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.value.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'mailer_from_email',
            'text',
            array(
                'label'       => 'mautic.email.config.mailer.from.email',
                'label_attr'  => array('class' => 'control-label'),
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.from.email.tooltip'
                ),
                'constraints' => array(
                    new NotBlank(
                        array(
                            'message' => 'mautic.core.email.required'
                        )
                    ),
                    new Email(
                        array(
                            'message' => 'mautic.core.email.required'
                        )
                    )
                )
            )
        );

        $builder->add(
            'mailer_return_path',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.return.path',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.return.path.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_transport',
            'choice',
            array(
                'choices'     => array(
                    'mail'                      => 'mautic.email.config.mailer_transport.mail',
                    'mautic.transport.mandrill' => 'mautic.email.config.mailer_transport.mandrill',
                    'mautic.transport.sendgrid' => 'mautic.email.config.mailer_transport.sendgrid',
                    'mautic.transport.amazon'   => 'mautic.email.config.mailer_transport.amazon',
                    'mautic.transport.postmark' => 'mautic.email.config.mailer_transport.postmark',
                    'gmail'                     => 'mautic.email.config.mailer_transport.gmail',
                    'sendmail'                  => 'mautic.email.config.mailer_transport.sendmail',
                    'smtp'                      => 'mautic.email.config.mailer_transport.smtp'
                ),
                'label'       => 'mautic.email.config.mailer.transport',
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.transport.tooltip'
                ),
                'empty_value' => false
            )
        );

        $builder->add(
            'mailer_convert_embed_images',
            'yesno_button_group',
            array(
                'label'      => 'mautic.email.config.mailer.convert.embed.images',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'      => 'form-control',
                    'tooltip'    => 'mautic.email.config.mailer.convert.embed.images.tooltip',

                ),
                'data'       => empty($options['data']['mailer_convert_embed_images']) ? false : true,
                'required'   => false
            )
        );

        $builder->add(
            'mailer_append_tracking_pixel',
            'yesno_button_group',
            array(
                'label'      => 'mautic.email.config.mailer.append.tracking.pixel',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'      => 'form-control',
                    'tooltip'    => 'mautic.email.config.mailer.append.tracking.pixel.tooltip',

                ),
                'data'       => empty($options['data']['mailer_append_tracking_pixel']) ? false : true,
                'required'   => false
            )
        );

        $smtpServiceShowConditions = '{"config_emailconfig_mailer_transport":["smtp"]}';
        $amazonRegionShowConditions = '{"config_emailconfig_mailer_transport":["mautic.transport.amazon"]}';

        $builder->add(
            'mailer_host',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.host',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.host.tooltip'
                ),
                'required'   => false
            )
        );


        $builder->add(
            'mailer_amazon_region',
            'choice',
            array(
                'choices'     => array(
                    'email-smtp.eu-west-1.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.eu_west_1',
                    'email-smtp.us-east-1.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.us_east_1',
                    'email-smtp.us-west-2.amazonaws.com' => 'mautic.email.config.mailer.amazon_host.eu_west_2'
                ),
                'label'       => 'mautic.email.config.mailer.amazon_host',
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'data-show-on' => $amazonRegionShowConditions,
                    'tooltip' => 'mautic.email.config.mailer.amazon_host.tooltip'
                ),
                'empty_value' => false
            )
        );

        $builder->add(
            'mailer_port',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.port',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.port.tooltip'
                ),
                'required'   => false
            )
        );

        $mailerLoginShowConditions = '{
            "config_emailconfig_mailer_auth_mode":[
                "plain",
                "login",
                "cram-md5"
            ], "config_emailconfig_mailer_transport":[
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "gmail"
            ]
        }';

        $mailerLoginHideConditions = '{
         "config_emailconfig_mailer_transport":[
                "mail",
                "sendmail"
            ]
        }';

        $builder->add(
            'mailer_auth_mode',
            'choice',
            array(
                'choices'     => array(
                    'plain'    => 'mautic.email.config.mailer_auth_mode.plain',
                    'login'    => 'mautic.email.config.mailer_auth_mode.login',
                    'cram-md5' => 'mautic.email.config.mailer_auth_mode.cram-md5'
                ),
                'label'       => 'mautic.email.config.mailer.auth.mode',
                'label_attr'  => array('class' => 'control-label'),
                'required'    => false,
                'attr'        => array(
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.auth.mode.tooltip'
                ),
                'empty_value' => 'mautic.email.config.mailer_auth_mode.none'
            )
        );

        $builder->add(
            'mailer_user',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.user',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-show-on' => $mailerLoginShowConditions,
                    'data-hide-on' => $mailerLoginHideConditions,
                    'tooltip'      => 'mautic.email.config.mailer.user.tooltip',
                    'autocomplete' => 'off'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_password',
            'password',
            array(
                'label'      => 'mautic.email.config.mailer.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.form.passwordplaceholder',
                    'preaddon'     => 'fa fa-lock',
                    'data-show-on' => $mailerLoginShowConditions,
                    'data-hide-on' => $mailerLoginHideConditions,
                    'tooltip'      => 'mautic.email.config.mailer.password.tooltip',
                    'autocomplete' => 'off'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_encryption',
            'choice',
            array(
                'choices'     => array(
                    'ssl' => 'mautic.email.config.mailer_encryption.ssl',
                    'tls' => 'mautic.email.config.mailer_encryption.tls'
                ),
                'label'       => 'mautic.email.config.mailer.encryption',
                'required'    => false,
                'attr'        => array(
                    'class'        => 'form-control',
                    'data-show-on' => $smtpServiceShowConditions,
                    'tooltip'      => 'mautic.email.config.mailer.encryption.tooltip'
                ),
                'empty_value' => 'mautic.email.config.mailer_encryption.none'
            )
        );

        $builder->add(
            'mailer_test_connection_button',
            'standalone_button',
            array(
                'label'    => 'mautic.email.config.mailer.transport.test_connection',
                'required' => false,
                'attr'     => array(
                    'class'   => 'btn btn-success',
                    'onclick' => 'Mautic.testEmailServerConnection()'
                )
            )
        );

        $builder->add(
            'mailer_test_send_button',
            'standalone_button',
            array(
                'label'    => 'mautic.email.config.mailer.transport.test_send',
                'required' => false,
                'attr'     => array(
                    'class'   => 'btn btn-info',
                    'onclick' => 'Mautic.testEmailServerConnection(true)'
                )
            )
        );

        $spoolConditions = '{"config_emailconfig_mailer_spool_type":["memory"]}';

        $builder->add(
            'mailer_spool_type',
            'choice',
            array(
                'choices'     => array(
                    'memory' => 'mautic.email.config.mailer_spool_type.memory',
                    'file'   => 'mautic.email.config.mailer_spool_type.file'
                ),
                'label'       => 'mautic.email.config.mailer.spool.type',
                'label_attr'  => array('class' => 'control-label'),
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.config.mailer.spool.type.tooltip'
                ),
                'empty_value' => false
            )
        );

        $builder->add(
            'mailer_spool_path',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.spool.path',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.path.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_spool_msg_limit',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.spool.msg.limit',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.msg.limit.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_spool_time_limit',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.spool.time.limit',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.time.limit.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_spool_recover_timeout',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.spool.recover.timeout',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.recover.timeout.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'mailer_spool_clear_timeout',
            'text',
            array(
                'label'      => 'mautic.email.config.mailer.spool.clear.timeout',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'data-hide-on' => $spoolConditions,
                    'tooltip'      => 'mautic.email.config.mailer.spool.clear.timeout.tooltip'
                ),
                'required'   => false
            )
        );

        $builder->add(
            'monitored_email',
            'monitored_email',
            array(
                'label'    => false,
                'data'     => (array_key_exists('monitored_email', $options['data'])) ? $options['data']['monitored_email'] : array(),
                'required' => false
            )
        );

        $builder->add(
            'mailer_is_owner',
            'yesno_button_group',
            array(
                'label'      => 'mautic.email.config.mailer.is.owner',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'      => 'form-control',
                    'tooltip'    => 'mautic.email.config.mailer.is.owner.tooltip',
                ),
                'data'       => empty($options['data']['mailer_is_owner']) ? false : true,
                'required'   => false
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'emailconfig';
    }
}
