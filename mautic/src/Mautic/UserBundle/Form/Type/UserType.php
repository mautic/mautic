<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserType
 *
 * @package Mautic\UserBundle\Form\Type
 */
class UserType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text', array(
                'label'      => 'mautic.user.form.username',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')

            ))
            ->add('firstName', 'text', array(
                'label'      => 'mautic.user.form.firstname',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')

            ))
            ->add('lastName',  'text', array(
                'label'      => 'mautic.user.form.lastname',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ))
            ->add('email',     'email', array(
                'label'      => 'mautic.user.form.email',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ))
            ->add('password', 'repeated', array(
                'first_name'        => 'password',
                'first_options'     => array(
                    'label'      => 'mautic.user.form.password',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control')
                ),
                'second_name'       => 'confirm',
                'second_options'    => array(
                    'label'      => 'mautic.user.form.passwordconfirm',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control')
                ),
                'type'              => 'password',
                'invalid_message'   => 'mautic.user.password.mismatch'
            ))
            ->add('save', 'submit', array(
                'label' => 'mautic.form.save',
                'attr'  => array('class' => 'btn btn-primary'),
            ))
            ->add('reset', 'reset', array(
                'label' => 'mautic.form.reset',
                'attr'  => array('class' => 'btn btn-danger'),
            ));
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\UserBundle\Entity\User',
            'validation_groups' => array(
                'Mautic\UserBundle\Entity\User',
                'determineValidationGroups',
            ),
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "user";
    }
}