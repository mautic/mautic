<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UserInviteRegistrationType extends AbstractType
{
    public function __construct(
        private readonly LanguageHelper $languageHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'label'      => 'mautic.core.username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'preaddon'     => 'ri-user-6-fill',
                    'autocomplete' => 'off',
                ],
                'required' => true,
            ]
        );

        $builder->add(
            'firstName',
            TextType::class,
            [
                'label'      => 'mautic.core.firstname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'lastName',
            TextType::class,
            [
                'label'      => 'mautic.core.lastname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => true,
            ]
        );

        $builder->add(
            'plainPassword',
            RepeatedType::class,
            [
                'first_name'    => 'password',
                'first_options' => [
                    'label'      => 'mautic.core.password',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                        'preaddon'     => 'ri-lock-fill',
                        'autocomplete' => 'off',
                    ],
                    'required'       => true,
                    'error_bubbling' => false,
                ],
                'second_name'    => 'confirm',
                'second_options' => [
                    'label'      => 'mautic.user.user.form.passwordconfirm',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'preaddon'     => 'ri-lock-fill',
                        'autocomplete' => 'off',
                    ],
                    'required'       => true,
                    'error_bubbling' => false,
                ],
                'type'            => PasswordType::class,
                'invalid_message' => 'mautic.user.user.password.mismatch',
                'required'        => true,
                'error_bubbling'  => false,
            ]
        );

        $builder->add(
            'locale',
            ChoiceType::class,
            [
                'choices'           => $this->getSupportedLanguageChoices(),
                'label'             => 'mautic.core.language',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => [
                    'class' => 'form-control',
                ],
                'multiple'    => false,
                'placeholder' => 'mautic.user.user.form.defaultlocale',
                'required'    => false,
            ]
        );

        $builder->add('buttons', FormButtonsType::class, [
            'save_text'   => 'mautic.user.invite.register',
            'apply_text'  => false,
            'cancel_text' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'         => User::class,
                'validation_groups'  => [
                    User::class,
                    'determineValidationGroups',
                ],
                'ignore_formexit'    => true,
                'allow_extra_fields' => true,
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    private function getSupportedLanguageChoices(): array
    {
        $languages = $this->languageHelper->fetchLanguages(false, false);
        $choices   = [];

        foreach ($languages as $code => $langData) {
            $choices[$langData['name']] = $code;
        }
        $choices = array_merge($choices, array_flip($this->languageHelper->getSupportedLanguages()));

        ksort($choices);

        return $choices;
    }
}
