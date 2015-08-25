<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Email Form Type.
 */
class EmailStepType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('mailer_from_name', 'text', array(
            'label'      => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'placeholder' => 'mautic.install.form.email.from_name'
            ),
            'required'   => true,
            'constraints' => array(
                new NotBlank(
                    array(
                        'message' => 'mautic.core.value.required'
                    )
                )
            )
        ));

        $builder->add('mailer_from_email', 'email', array(
            'label'      => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'preaddon' => 'fa fa-envelope',
                'placeholder' => 'mautic.install.form.email.from_address',
            ),
            'required'   => true,
            'constraints' => array(
                new NotBlank(
                    array(
                        'message' => 'mautic.core.value.required'
                    )
                ),
                new Email(
                    array(
                        'message' => 'mautic.core.email.required'
                    )
                )
            )
        ));

        $builder->add('mailer_transport', 'choice', array(
            'choices' => array(
                'mail'     => 'mautic.core.config.mailer_transport.mail',
                'mautic.transport.mandrill' => 'mautic.core.config.mailer_transport.mandrill',
                'mautic.transport.sendgrid' => 'mautic.core.config.mailer_transport.sendgrid',
                'mautic.transport.amazon'   => 'mautic.core.config.mailer_transport.amazon',
                'mautic.transport.postmark'   => 'mautic.core.config.mailer_transport.postmark',
                'gmail'    => 'mautic.core.config.mailer_transport.gmail',
                'smtp'     => 'mautic.core.config.mailer_transport.smtp',
                'sendmail' => 'mautic.core.config.mailer_transport.sendmail'
            ),
            'label'       => 'mautic.install.form.email.transport',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'attr'       => array(
                'class'    => 'form-control',
                'tooltip'  => 'mautic.install.form.email.transport_descr',
                'onchange' => 'MauticInstaller.toggleTransportDetails(this.value);'
            )
        ));

        $builder->add('mailer_host', 'text', array(
            'label'      => 'mautic.install.form.email.mailer_host',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('mailer_port', 'text', array(
            'label'      => 'mautic.install.form.email.mailer_port',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('mailer_user', 'text', array(
            'label'      => 'mautic.core.username',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('mailer_password', 'password', array(
            'label'      => 'mautic.core.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class' => 'form-control',
                'preaddon' => 'fa fa-lock'
            )
        ));

        $builder->add('mailer_encryption', 'button_group', array(
            'choice_list' => new ChoiceList(
                array('tls', 'ssl'),
                array('mautic.core.config.mailer_encryption.tls', 'mautic.core.config.mailer_encryption.ssl')
            ),
            'label'       => 'mautic.install.form.email.encryption',
            'expanded'    => true,
            'empty_value' => 'mautic.install.form.none'
        ));

        $builder->add('mailer_auth_mode', 'choice', array(
            'choice_list' => new ChoiceList(
                array(
                    'plain',
                    'login',
                    'cram-md5'
                ),
                array(
                    'mautic.core.config.mailer_auth_mode.plain',
                    'mautic.core.config.mailer_auth_mode.login',
                    'mautic.core.config.mailer_auth_mode.cram-md5'
                )
            ),
            'label'       => 'mautic.install.form.email.auth_mode',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => 'mautic.install.form.none',
            'attr'       => array(
                'class'   => 'form-control',
                'onchange' => 'MauticInstaller.toggleAuthDetails(this.value);'
            )
        ));

        $builder->add('mailer_spool_type', 'button_group', array(
            'choice_list' => new ChoiceList(
                array('memory', 'file'),
                array(
                    'mautic.core.config.mailer_spool_type.memory',
                    'mautic.core.config.mailer_spool_type.file'
                )
            ),
            'label'       => 'mautic.install.form.email.spool_type',
            'expanded'    => true,
            'empty_value' => false,
            'attr'        => array(
                'onchange' => 'MauticInstaller.toggleSpoolQueue();',
                'tooltip'  => 'mautic.core.config.form.mailer.spool.type.tooltip'
            )
        ));

        $builder->add('mailer_spool_path', 'hidden');

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'next',
                    'label' => 'mautic.install.next.step',
                    'type'  => 'submit',
                    'attr'  => array(
                        'class' => 'btn btn-success pull-right btn-next',
                        'icon'  => 'fa fa-arrow-circle-right',
                        'onclick' => 'MauticInstaller.showWaitMessage(event);'
                    )
                )
            ),
            'apply_text'        => '',
            'save_text'         => '',
            'cancel_text'       => ''
        ));


        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'install_email_step';
    }
}
