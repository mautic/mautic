<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PasswordResetType
 */
class PasswordResetConfirmType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('identifier', 'text', array(
            'label'      => 'mautic.user.auth.form.loginusername',
            'label_attr' => array('class' => 'sr-only'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon'    => 'fa fa-user',
                'placeholder' => 'mautic.user.auth.form.loginusername'
            ),
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(array('message' => 'mautic.user.user.passwordreset.notblank'))
            )
        ));

        $builder->add('plainPassword', 'repeated', array(
            'first_name'        => 'password',
            'first_options'     => array(
                'label'      => 'mautic.core.password',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.passwordreset.password.placeholder',
                    'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'     => 'fa fa-lock',
                    'autocomplete' => 'off'
                ),
                'required'   => true,
                'error_bubbling'    => false,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => 'mautic.user.user.passwordreset.notblank')),
                    new Assert\Length(array(
                        'min' => 6,
                        'minMessage' => 'mautic.user.user.password.minlength'
                    ))
                )
            ),
            'second_name'       => 'confirm',
            'second_options'    => array(
                'label'      => 'mautic.user.user.form.passwordconfirm',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.passwordreset.confirm.placeholder',
                    'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'     => 'fa fa-lock',
                    'autocomplete' => 'off'
                ),
                'required'   => true,
                'error_bubbling'    => false,
                'constraints' => array(
                    new Assert\NotBlank(array('message' => 'mautic.user.user.passwordreset.notblank'))
                )
            ),
            'type'              => 'password',
            'invalid_message'   => 'mautic.user.user.password.mismatch',
            'required'          => true,
            'error_bubbling'    => false
        ));

        $builder->add('submit', 'submit', array(
            'attr'     => array(
                'class'   => 'btn btn-lg btn-primary btn-block',
            ),
            'label'    => 'mautic.user.user.passwordreset.reset'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "passwordresetconfirm";
    }
}
