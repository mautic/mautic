<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FormSubmitActionSendAdminEmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class FormSubmitActionSendAdminEmailType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'email_list', array(
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.email.form.submit.emails',
            'label_attr'    => array('class' => 'control-label'),
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.submit.emails_descr'
            )
        ));

        $builder->add('user_lookup', 'text', array(
            'label'      => 'mautic.email.form.users',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'tooltip'     => 'mautic.core.help.autocomplete'
            ),
            // 'mapped'     => false, // @todo load user name from the controller
            'required'   => false
        ));

        $builder->add('user_id', 'hidden', array(
            'required'       => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "email_submitaction_sendemail_admin";
    }
}