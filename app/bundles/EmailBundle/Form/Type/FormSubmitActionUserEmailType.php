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
 * Class FormSubmitActionUserEmailType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class FormSubmitActionUserEmailType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('useremail', 'emailsend_list', array(
            'label'         => 'mautic.email.emails',
            'attr'       => array(
                'class'   => 'form-control',
                'tooltip' => 'mautic.email.choose.emails_descr'
            ),
            'update_select' => 'formaction_properties_useremail_email'
        ));

        $builder->add('user_id', 'user_list', array(
            'label'      => 'mautic.email.form.users',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'       => 'form-control',
                'tooltip'     => 'mautic.core.help.autocomplete'
            ),
            'required'   => false
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'label' => false
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "email_submitaction_useremail";
    }
}