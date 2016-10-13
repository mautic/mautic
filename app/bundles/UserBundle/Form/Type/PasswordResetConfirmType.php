<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class PasswordResetType.
 */
class PasswordResetConfirmType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('identifier', 'text', [
            'label'      => 'mautic.user.auth.form.loginusername',
            'label_attr' => ['class' => 'sr-only'],
            'attr'       => [
                'class'       => 'form-control',
                'preaddon'    => 'fa fa-user',
                'placeholder' => 'mautic.user.auth.form.loginusername',
            ],
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(['message' => 'mautic.user.user.passwordreset.notblank']),
            ],
        ]);

        $builder->add('plainPassword', 'repeated', [
            'first_name'    => 'password',
            'first_options' => [
                'label'      => 'mautic.core.password',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.passwordreset.password.placeholder',
                    'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'     => 'fa fa-lock',
                    'autocomplete' => 'off',
                ],
                'required'       => true,
                'error_bubbling' => false,
                'constraints'    => [
                    new Assert\NotBlank(['message' => 'mautic.user.user.passwordreset.notblank']),
                    new Assert\Length([
                        'min'        => 6,
                        'minMessage' => 'mautic.user.user.password.minlength',
                    ]),
                ],
            ],
            'second_name'    => 'confirm',
            'second_options' => [
                'label'      => 'mautic.user.user.form.passwordconfirm',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => 'mautic.user.user.passwordreset.confirm.placeholder',
                    'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                    'preaddon'     => 'fa fa-lock',
                    'autocomplete' => 'off',
                ],
                'required'       => true,
                'error_bubbling' => false,
                'constraints'    => [
                    new Assert\NotBlank(['message' => 'mautic.user.user.passwordreset.notblank']),
                ],
            ],
            'type'            => 'password',
            'invalid_message' => 'mautic.user.user.password.mismatch',
            'required'        => true,
            'error_bubbling'  => false,
        ]);

        $builder->add('submit', 'submit', [
            'attr' => [
                'class' => 'btn btn-lg btn-primary btn-block',
            ],
            'label' => 'mautic.user.user.passwordreset.reset',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'passwordresetconfirm';
    }
}
