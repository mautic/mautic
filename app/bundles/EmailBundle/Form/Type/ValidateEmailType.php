<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Mautic\EmailBundle\Validator\EmailAddressMatchesLink;
use Mautic\LeadBundle\Form\Validator\Constraints\EmailAddress;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ValidateEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'emailAddress',
            EmailType::class,
            [
                'label'       => 'mautic.email.address.to.validate',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'         => 'form-control',
                    'autocomplete'  => 'email',
                    'autocapitalize'=> 'off',
                    'autocorrect'   => 'off',
                    'spellcheck'    => 'false',
                    'autofocus'     => 'autofocus',
                ],
                'constraints' => [
                    new NotBlank(),
                    new EmailAddress(),
                    new EmailAddressMatchesLink(
                        [
                            'secretHash'       => $options['secret_hash'],
                            'statEmailAddress' => $options['stat_email_address'],
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'submit',
            SubmitType::class,
            [
                'label' => 'mautic.email.address.validate',
                'attr'  => [
                    'class' => 'btn btn-primary',
                    'icon'  => 'fa-check',
                ],
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['secret_hash']);
        $resolver->setDefaults(['stat_email_address' => null]);
        $resolver->setAllowedTypes('secret_hash', 'string');
        $resolver->setAllowedTypes('stat_email_address', ['null', 'string']);
    }
}
