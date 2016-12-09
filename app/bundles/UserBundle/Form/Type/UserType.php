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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\UserBundle\Model\UserModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class UserType.
 */
class UserType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * @var bool|mixed
     */
    private $supportedLanguages;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var \Mautic\UserBundle\Model\UserModel
     */
    private $model;

    /**
     * UserType constructor.
     *
     * @param TranslatorInterface  $translator
     * @param EntityManager        $em
     * @param UserModel            $model
     * @param LanguageHelper       $languageHelper
     * @param CoreParametersHelper $parametersHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        EntityManager $em,
        UserModel $model,
        LanguageHelper $languageHelper,
        CoreParametersHelper $parametersHelper
    ) {
        $this->translator = $translator;
        $this->em         = $em;
        $this->model      = $model;

        // Get the list of available languages
        $languages   = $languageHelper->fetchLanguages(false, false);
        $langChoices = [];

        foreach ($languages as $code => $langData) {
            $langChoices[$code] = $langData['name'];
        }

        $langChoices = array_merge($langChoices, $parametersHelper->getParameter('supported_languages'));

        // Alpha sort the languages by name
        asort($langChoices);

        $this->supportedLanguages = $langChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('user.user', $options));

        $builder->add(
            'username',
            'text',
            [
                'label'      => 'mautic.core.username',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'preaddon'     => 'fa fa-user',
                    'autocomplete' => 'off',
                ],
            ]
        );

        $builder->add(
            'firstName',
            'text',
            [
                'label'      => 'mautic.core.firstname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'lastName',
            'text',
            [
                'label'      => 'mautic.core.lastname',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $positions = $this->model->getLookupResults('position', null, 0, true);
        $builder->add(
            'position',
            'text',
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
            'email',
            [
                'label'      => 'mautic.core.type.email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-envelope',
                ],
            ]
        );

        $existing    = (!empty($options['data']) && $options['data']->getId());
        $placeholder = ($existing) ?
            $this->translator->trans('mautic.user.user.form.passwordplaceholder') : '';
        $required = ($existing) ? false : true;
        $builder->add(
            'plainPassword',
            'repeated',
            [
                'first_name'    => 'password',
                'first_options' => [
                    'label'      => 'mautic.core.password',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'placeholder'  => $placeholder,
                        'tooltip'      => 'mautic.user.user.form.help.passwordrequirements',
                        'preaddon'     => 'fa fa-lock',
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
                        'preaddon'     => 'fa fa-lock',
                        'autocomplete' => 'off',
                    ],
                    'required'       => $required,
                    'error_bubbling' => false,
                ],
                'type'            => 'password',
                'invalid_message' => 'mautic.user.user.password.mismatch',
                'required'        => $required,
                'error_bubbling'  => false,
            ]
        );

        $builder->add(
            'timezone',
            'timezone',
            [
                'label'      => 'mautic.core.timezone',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple'    => false,
                'empty_value' => 'mautic.user.user.form.defaulttimezone',
            ]
        );

        $builder->add(
            'locale',
            'choice',
            [
                'choices'    => $this->supportedLanguages,
                'label'      => 'mautic.core.language',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class' => 'form-control',
                ],
                'multiple'    => false,
                'empty_value' => 'mautic.user.user.form.defaultlocale',
            ]
        );

        $defaultSignature = '';
        if (isset($options['data']) && $options['data']->getSignature() === null) {
            $defaultSignature = $this->translator->trans('mautic.email.default.signature', ['%from_name%' => '|FROM_NAME|']);
        } elseif (isset($options['data'])) {
            $defaultSignature = $options['data']->getSignature();
        }

        $builder->add(
            'signature',
            'textarea',
            [
                'label'      => 'mautic.email.token.signature',
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class' => 'form-control',
                ],
                'data' => $defaultSignature,
            ]
        );

        if (empty($options['in_profile'])) {
            $builder->add(
                $builder->create(
                    'role',
                    'entity',
                    [
                        'label'      => 'mautic.user.role',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'class'         => 'MauticUserBundle:Role',
                        'property'      => 'name',
                        'query_builder' => function (EntityRepository $er) {
                            return $er->createQueryBuilder('r')
                                ->where('r.isPublished = true')
                                ->orderBy('r.name', 'ASC');
                        },
                    ]
                )
            );

            $builder->add('isPublished', 'yesno_button_group');

            $builder->add('buttons', 'form_buttons');
        } else {
            $builder->add(
                'buttons',
                'form_buttons',
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

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class'        => 'Mautic\UserBundle\Entity\User',
                'validation_groups' => [
                    'Mautic\UserBundle\Entity\User',
                    'determineValidationGroups',
                ],
                'ignore_formexit' => false,
                'in_profile'      => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'user';
    }
}
