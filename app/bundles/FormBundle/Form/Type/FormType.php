<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\PublishDownDateType;
use Mautic\CoreBundle\Form\Type\PublishUpDateType;
use Mautic\CoreBundle\Form\Type\ThemeListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\LanguageHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Form>
 */
class FormType extends AbstractType
{
    public function __construct(
        private CorePermissions $security,
        private LanguageHelper $langHelper,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('form.form', $options));

        // details
        $builder->add('name', TextType::class, [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

        $builder->add(
            'language',
            ChoiceType::class,
            [
                'choices'           => $this->langHelper->getLanguageChoices(),
                'label'             => 'mautic.core.config.form.locale',
                'required'          => false,
                'attr'              => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.form.form.locale.tooltip',
                ],
                'placeholder'       => '',
            ]
        );

        $builder->add('formAttributes', TextType::class, [
            'label'      => 'mautic.form.field.form.form_attr',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.field.form.form_attr.tooltip',
            ],
            'required'   => false,
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control editor'],
            'required'   => false,
        ]);

        // add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'form',
            ]
        );

        $builder->add('template', ThemeListType::class, [
            'feature'     => 'form',
            'placeholder' => ' ',
            'attr'        => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.form.form.template.help',
            ],
        ]);

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->hasEntityAccess(
                'form:forms:publishown',
                'form:forms:publishother',
                $options['data']->getCreatedBy()
            );

            $data = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('form:forms:publishown')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = true;
        }

        $builder->add('isPublished', YesNoButtonGroupType::class, [
            'label' => 'mautic.core.form.available',
            'data'  => $data,
            'attr'  => [
                'readonly' => $readonly,
            ],
        ]);

        $builder->add('inKioskMode', YesNoButtonGroupType::class, [
            'label' => 'mautic.form.form.kioskmode',
            'attr'  => [
                'tooltip' => 'mautic.form.form.kioskmode.tooltip',
            ],
        ]);

        $builder->add(
            'noIndex',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.form.no_index',
                'data'  => $options['data']->getNoIndex() ?: false,
            ]
        );

        $builder->add(
            'progressiveProfilingLimit',
            TextType::class,
            [
                'label' => 'mautic.form.form.progressive_profiling_limit.max_fields',
                'attr'  => [
                    'style'       => 'width:100px;',
                    'class'       => 'form-control',
                    'tooltip'     => 'mautic.form.form.progressive_profiling_limit.max_fields.tooltip',
                    'placeholder' => 'mautic.form.form.progressive_profiling_limit_unlimited',
                ],
                'data'  => $options['data']->getProgressiveProfilingLimit() ?: '',
            ]
        );

        // Render style for new form by default
        if (null === $options['data']->getId()) {
            $options['data']->setRenderStyle(true);
        }

        $builder->add('renderStyle', YesNoButtonGroupType::class, [
            'label'      => 'mautic.form.form.renderstyle',
            'data'       => $options['data']->getRenderStyle() ?? true,
            'attr'       => [
                'tooltip' => 'mautic.form.form.renderstyle.tooltip',
            ],
        ]);

        $builder->add('publishUp', PublishUpDateType::class);
        $builder->add('publishDown', PublishDownDateType::class);

        $builder->add('postAction', ChoiceType::class, [
            'choices' => [
                'mautic.form.form.postaction.message'  => 'message',
                'mautic.form.form.postaction.redirect' => 'redirect',
                'mautic.form.form.postaction.return'   => 'return',
            ],
            'label'             => 'mautic.form.form.postaction',
            'label_attr'        => ['class' => 'control-label'],
            'attr'              => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.onPostSubmitActionChange(this.value);',
            ],
            'required'    => false,
            'placeholder' => false,
        ]);

        $postAction = (isset($options['data'])) ? $options['data']->getPostAction() : '';
        $required   = (in_array($postAction, ['redirect', 'message'])) ? true : false;
        $builder->add('postActionProperty', TextType::class, [
            'label'      => 'mautic.form.form.postactionproperty',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'         => 'form-control',
                'tooltip'       => 'mautic.form.form.postactionproperty.tooltip',
                'data-hide-on'  => '{"mauticform_postAction":"return"}',
            ],
            'required'   => $required,
        ]);

        $builder->add('sessionId', HiddenType::class, [
            'mapped' => false,
        ]);

        $builder->add('buttons', FormButtonsType::class);
        $builder->add('formType', HiddenType::class, ['empty_data' => 'standalone']);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => Form::class,
            'validation_groups' => [
                Form::class,
                'determineValidationGroups',
            ],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'mauticform';
    }
}
