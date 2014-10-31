<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FormSubmitActionSendEmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class FormSubmitActionSendEmailType extends AbstractType
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
            'empty_value'   => false,
            'required'      => false,
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.submit.emails_descr'
            )
        ));

        $builder->add('message', 'textarea', array(
            'label'         => 'mautic.email.form.submit.message',
            'label_attr'    => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.form.submit.message_descr'
            )
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "email_submitaction_sendemail";
    }
}