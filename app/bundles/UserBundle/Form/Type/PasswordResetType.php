<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
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
class PasswordResetType extends AbstractType
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
            'constraints' => [
                new Assert\NotBlank(['message' => 'mautic.user.user.passwordreset.notblank']),
            ],
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
        return 'passwordreset';
    }
}
