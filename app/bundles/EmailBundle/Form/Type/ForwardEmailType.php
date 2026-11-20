<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Asks for the address a copy of a sent email should go to.
 */
final class ForwardEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'emailAddress',
            EmailType::class,
            [
                'label'       => 'mautic.email.forward.address',
                'label_attr'  => ['class' => 'form-label'],
                'attr'        => [
                    'class'          => 'form-control',
                    'autocomplete'   => 'email',
                    'autocapitalize' => 'off',
                    'autocorrect'    => 'off',
                    'spellcheck'     => 'false',
                    'autofocus'      => 'autofocus',
                ],
                'constraints' => [
                    new NotBlank(),
                    new EmailAddress(),
                ],
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'mautic.email.forward.submit',
                'attr'  => [
                    'class' => 'btn btn-primary',
                    'icon'  => 'fa-send',
                ],
            ]
        );
    }
}
