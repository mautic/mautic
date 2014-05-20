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
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('msg_subject', 'text', array(
                'label'      => 'mautic.user.user.contact.subject',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'constraints' => array(
                    new NotBlank(array('message' => 'Subject should not be blank.')),
                    new Length(array('min' => 3))
                )
            ))
            ->add('msg_body', 'textarea', array(
                'label'      => 'mautic.user.user.contact.message',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class' => 'form-control',
                    'rows'  => 10
                ),
                'constraints' => array(
                    new NotBlank(array('message' => 'Message should not be blank.')),
                    new Length(array('min' => 5))
                )
            ))
            ->add('entity', 'hidden', array(
                'attr' => array(
                    'autocomplete' => 'off'
                )
            ))
            ->add('id', 'hidden', array(
                'attr' => array(
                    'autocomplete' => 'off'
                )
            ))
            ->add('returnUrl', 'hidden', array(
                'attr' => array(
                    'autocomplete' => 'off'
                )
            ))
            ->add('save', 'submit', array(
                'label' => 'mautic.user.user.contact.send',
                'attr'  => array(
                    'class' => 'btn btn-primary',
                    'icon'  => 'fa fa-send padding-sm-right'
                )
            ))
            ->add('cancel', 'submit', array(
                'label' => 'mautic.core.form.cancel',
                'attr'  => array(
                    'class'   => 'btn btn-danger',
                    'icon'    => 'fa fa-times padding-sm-right'
                )
            ));

    }

    public function getName()
    {
        return 'contact';
    }
}