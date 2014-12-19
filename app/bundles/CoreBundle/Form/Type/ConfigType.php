<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\ConfigBundle\Form\Type\ConfigType as ConfigParentType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ConfigType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class ConfigType extends ConfigParentType
{
    /**
     * @var MauticFactory
     */
    private $factory;

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
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('site_url', 'text', array(
            'label'      => 'mautic.core.config.form.site.url',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('cache_path', 'text', array(
            'label'      => 'mautic.core.config.form.cache.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('log_path', 'text', array(
            'label'      => 'mautic.core.config.form.log.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('image_path', 'text', array(
            'label'      => 'mautic.core.config.form.image.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('theme', 'choice', array(
            'choices'  => $this->factory->getInstalledThemes(),
            'label'    => 'mautic.core.config.form.theme',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('mailer_from_name', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.from.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('mailer_from_email', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.from.email',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('mailer_transport', 'choice', array(
            'choices'  => array(
                'mail'     => 'mautic.core.config.mailer_transport.mail',
                'mautic.transport.mandrill' => 'mautic.core.config.mailer_transport.mandrill',
                'mautic.transport.sendgrid' => 'mautic.core.config.mailer_transport.sendgrid',
                'mautic.transport.amazon'   => 'mautic.core.config.mailer_transport.amazon',
                'mautic.transport.postmark'   => 'mautic.core.config.mailer_transport.postmark',
                'gmail'    => 'mautic.core.config.mailer_transport.gmail',
                'sendmail' => 'mautic.core.config.mailer_transport.sendmail',
                'smtp'     => 'mautic.core.config.mailer_transport.smtp'
            ),
            'label'    => 'mautic.core.config.form.mailer.transport',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $smtpServiceShowConditions = '{"config_CoreBundle_mailer_transport":["smtp"]}';

        $builder->add('mailer_host', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.host',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'         => 'form-control',
                'data-show-on'  => $smtpServiceShowConditions
            )
        ));

        $builder->add('mailer_port', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.port',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'         => 'form-control',
                'data-show-on'  => $smtpServiceShowConditions
            )
        ));

        $mailerLoginShowConditions = '{
            "config_CoreBundle_mailer_auth_mode":[
                "plain",
                "login",
                "cram-md5"
            ], "config_CoreBundle_mailer_transport":[
                "mautic.transport.mandrill",
                "mautic.transport.sendgrid",
                "mautic.transport.amazon",
                "mautic.transport.postmark",
                "gmail",
                "sendmail"
            ]
        }';

        $builder->add('mailer_auth_mode', 'choice', array(
            'choices'  => array(
                'plain'    => 'mautic.core.config.mailer_auth_mode.plain',
                'login'    => 'mautic.core.config.mailer_auth_mode.login',
                'cram-md5' => 'mautic.core.config.mailer_auth_mode.cram-md5'
            ),
            'label'    => 'mautic.core.config.form.mailer.auth.mode',
            'required' => false,
            'attr'       => array(
                'class'         => 'form-control',
                'data-show-on'  => $smtpServiceShowConditions
            )
        ));

        $builder->add('mailer_user', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.user',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'         => 'form-control',
                'data-show-on'  => $mailerLoginShowConditions
            )
        ));

        $builder->add('mailer_password', 'password', array(
            'label'      => 'mautic.core.config.form.mailer.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'         => 'form-control',
                'placeholder'   => 'mautic.user.user.form.passwordplaceholder',
                'preaddon'      => 'fa fa-lock',
                'data-show-on'  => $mailerLoginShowConditions
            )
        ));

        $builder->add('mailer_encryption', 'choice', array(
            'choices'  => array(
                'ssl' => 'mautic.core.config.mailer_encryption.ssl',
                'tls' => 'mautic.core.config.mailer_encryption.tls'
            ),
            'label'    => 'mautic.core.config.form.mailer.encryption',
            'required' => false,
            'attr'       => array(
                'class'         => 'form-control',
                'data-show-on'  => $smtpServiceShowConditions
            )
        ));

        $spoolConditions = '{"config_CoreBundle_mailer_spool_type":[""]}';

        $builder->add('mailer_spool_type', 'choice', array(
            'choices'  => array(
                'file'   => 'mautic.core.config.mailer_spool_type.file',
                'memory' => 'mautic.core.config.mailer_spool_type.memory'
            ),
            'label'    => 'mautic.core.config.form.mailer.spool.type',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('mailer_spool_path', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.spool.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-hide-on' => $spoolConditions
            )
        ));

        $builder->add('mailer_spool_msg_limit', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.spool.msg.limit',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-hide-on' => $spoolConditions
            )
        ));

        $builder->add('mailer_spool_time_limit', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.spool.time.limit',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-hide-on' => $spoolConditions
            )
        ));

        $builder->add('mailer_spool_recover_timeout', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.spool.recover.timeout',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-hide-on' => $spoolConditions
            )
        ));

        $builder->add('mailer_spool_clear_timeout', 'text', array(
            'label'      => 'mautic.core.config.form.mailer.spool.clear.timeout',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'data-hide-on' => $spoolConditions
            )
        ));

        $builder->add('locale', 'choice', array(
            'choices'  => $this->factory->getParameter('supported_languages'),
            'label'    => 'mautic.core.config.form.locale',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('trusted_hosts', 'text', array(
            'label'      => 'mautic.core.config.form.trusted.hosts',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('trusted_proxies', 'text', array(
            'label'      => 'mautic.core.config.form.trusted.proxies',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('rememberme_key', 'text', array(
            'label'      => 'mautic.core.config.form.rememberme.key',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('rememberme_lifetime', 'text', array(
            'label'      => 'mautic.core.config.form.rememberme.lifetime',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('rememberme_path', 'text', array(
            'label'      => 'mautic.core.config.form.rememberme.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('rememberme_domain', 'text', array(
            'label'      => 'mautic.core.config.form.rememberme.domain',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('default_pagelimit', 'text', array(
            'label'      => 'mautic.core.config.form.default.pagelimit',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('default_timezone', 'timezone', array(
            'label'       => 'mautic.core.config.form.default.timezone',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class' => 'form-control'
            ),
            'multiple'    => false,
            'empty_value' => 'mautic.user.user.form.defaulttimezone'
        ));

        $builder->add('date_format_full', 'text', array(
            'label'      => 'mautic.core.config.form.date.format.full',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('date_format_short', 'text', array(
            'label'      => 'mautic.core.config.form.date.format.short',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('date_format_dateonly', 'text', array(
            'label'      => 'mautic.core.config.form.date.format.dateonly',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('date_format_timeonly', 'text', array(
            'label'      => 'mautic.core.config.form.date.format.timeonly',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        // TODO - Write an API endpoint listing our supported services and build this list from that
        // see CoreBundle\Entity\IpAddress
        $builder->add('ip_lookup_service', 'choice', array(
            'choices'  => array(
                'telize'            => 'mautic.core.config.ip_lookup_service.telize',
                'freegeoip'         => 'mautic.core.config.ip_lookup_service.freegeoip',
                'geobytes'          => 'mautic.core.config.ip_lookup_service.geobytes',
                'ipinfodb'          => 'mautic.core.config.ip_lookup_service.ipinfodb',
                'geoips'            => 'mautic.core.config.ip_lookup_service.geoips',
                'maxmind_country'   => 'mautic.core.config.ip_lookup_service.maxmind_country',
                'maxmind_precision' => 'mautic.core.config.ip_lookup_service.maxmind_precision',
                'maxmind_omni'      => 'mautic.core.config.ip_lookup_service.maxmind_omni'
            ),
            'label'    => 'mautic.core.config.form.ip.lookup.service',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('ip_lookup_auth', 'text', array(
            'label'      => 'mautic.core.config.form.ip.lookup.auth',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('transifex_username', 'text', array(
            'label'      => 'mautic.core.config.form.transifex.username',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('transifex_password', 'password', array(
            'label'      => 'mautic.core.config.form.transifex.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.user.user.form.passwordplaceholder',
                'preaddon'    => 'fa fa-lock'
            )
        ));

        $builder->add('update_stability', 'choice', array(
            'choices'  => array(
                'alpha'  => 'mautic.core.config.update_stability.alpha',
                'beta'   => 'mautic.core.config.update_stability.beta',
                'rc'     => 'mautic.core.config.update_stability.rc',
                'stable' => 'mautic.core.config.update_stability.stable'
            ),
            'label'    => 'mautic.core.config.form.update.stability',
            'required' => false,
            'attr'     => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('cookie_path', 'text', array(
            'label'      => 'mautic.core.config.form.cookie.path',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('cookie_domain', 'text', array(
            'label'      => 'mautic.core.config.form.cookie.domain',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('cookie_secure', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.core.config.form.cookie.secure',
            'expanded'    => true,
            'empty_value' => 'mautic.core.form.default',
            'data'        => ($options['data']['cookie_secure'] === '' || $options['data']['cookie_secure'] === null) ? '' : (bool) $$options['data']['cookie_secure']
        ));

        $builder->add('cookie_httponly', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.core.config.form.cookie.httponly',
            'expanded'    => true,
            'empty_value' => false,
            'data'        => (bool)$options['data']['cookie_httponly']
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'coreconfig';
    }
}