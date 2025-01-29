<?php

namespace Mautic\UserBundle\Form\Type;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<User>
 */
class UserType extends AbstractType
{
    public function __construct(
        private TranslatorInterface $translator,
        private UserModel $model,
        private LanguageHelper $languageHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['signature' => 'html', 'email' => 'email']));
        $builder->addEventSubscriber(new FormExitSubscriber('user.user', $options));

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
            ]
        );

        $builder->add(
            'firstName',
            TextType::class,
            [
                'label'      => 'mautic.core.firstname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'lastName',
            TextType::class,
            [
                'label'      => 'mautic.core.lastname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $positions = $this->model->getLookupResults('position', null, 0);
        $builder->add(
            'position',
            TextType::class,
            [
                'label'      => 'mautic.core.position',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'data-options' => json_encode($positions),
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'email',
            EmailType::class,
            [
                'label'      => 'mautic.core.type.email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-mail-line',
                ],
            ]
        );

        $existing    = (!empty($options['data']) && $options['data']->getId());
        $placeholder = ($existing) ?
            $this->translator->trans('mautic.user.user.form.passwordplaceholder') : '';
        $required = ($existing) ? false : true;
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
                        'placeholder'  => $placeholder,
                        'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                        'preaddon'     => 'ri-lock-fill',
                        'autocomplete' => 'off',
                    ],
                    'required'       => $required,
                    'error_bubbling' => false,
                ],
                'second_name'    => 'confirm',
                'second_options' => [
                    'label'      => 'mautic.user.user.form.passwordconfirm',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'placeholder'  => $placeholder,
                        'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                        'preaddon'     => 'ri-lock-fill',
                        'autocomplete' => 'off',
                    ],
                    'required'       => $required,
                    'error_bubbling' => false,
                ],
                'type'            => PasswordType::class,
                'invalid_message' => 'mautic.user.user.password.mismatch',
                'required'        => $required,
                'error_bubbling'  => false,
            ]
        );

        $builder->add(
            'timezone',
            TimezoneType::class,
            [
                'label'      => 'mautic.core.timezone',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple'    => false,
                'placeholder' => 'mautic.user.user.form.defaulttimezone',
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
            ]
        );

        $builder->add(
            'preferences',
            UserPreferencesType::class,
            [
                'label'      => false,
            ]
        );

        $defaultSignature = '';
        if (isset($options['data']) && null === $options['data']->getSignature()) {
            $defaultSignature = $this->translator->trans('mautic.email.default.signature', ['%from_name%' => '|FROM_NAME|']);
        } elseif (isset($options['data'])) {
            $defaultSignature = $options['data']->getSignature();
        }

        $builder->add(
            'signature',
            TextareaType::class,
            [
                'label'      => 'mautic.email.token.signature',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'data' => $defaultSignature,
                'help' => 'mautic.user.config.signature.helper',
            ]
        );

        if (empty($options['in_profile'])) {
            $builder->add(
                $builder->create(
                    'role',
                    EntityType::class,
                    [
                        'label'      => 'mautic.user.role',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'class'         => Role::class,
                        'choice_label'  => 'name',
                        'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('r')
                            ->where('r.isPublished = true')
                            ->orderBy('r.name', Order::Ascending->value),
                    ]
                )
            );

            $builder->add('isPublished', YesNoButtonGroupType::class);

            $builder->add('buttons', FormButtonsType::class);
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'save_text'  => 'mautic.core.form.apply',
                    'apply_text' => false,
                ]
            );
        }

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'data_class'        => User::class,
                'validation_groups' => [
                    User::class,
                    'determineValidationGroups',
                ],
                'ignore_formexit' => false,
                'in_profile'      => false,
            ]
        );
    }

    private function getSupportedLanguageChoices(): array
    {
        // Get the list of available languages
        $languages = $this->languageHelper->fetchLanguages(false, false);
        $choices   = [];

        foreach ($languages as $code => $langData) {
            $choices[$langData['name']] = $code;
        }
        $choices = array_merge($choices, array_flip($this->languageHelper->getSupportedLanguages()));

        // Alpha sort the languages by name
        ksort($choices);

        return $choices;
    }
}
