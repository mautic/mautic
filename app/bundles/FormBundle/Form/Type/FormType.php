<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\ThemeListType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormType extends AbstractType
{
    /**
     * @var CorePermissions
     */
    private $security;

    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['description' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('form.form', $options));

        //details
        $builder->add('name', TextType::class, [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
        ]);

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

        //add category
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
            'data' => $data,
            'attr' => [
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
                'data'  => $options['data']->getNoIndex() ? $options['data']->getNoIndex() : false,
            ]
        );

        $builder->add(
            'progressiveProfilingLimit',
            TextType::class,
            [
                'label' => 'mautic.form.form.progressive_profiling_limit.max_fields',
                'attr'  => [
                    'style'       => 'width:75px;',
                    'class'       => 'form-control',
                    'tooltip'     => 'mautic.form.form.progressive_profiling_limit.max_fields.tooltip',
                    'placeholder' => 'mautic.form.form.progressive_profiling_limit_unlimited',
                ],
                'data'  => $options['data']->getProgressiveProfilingLimit() ? $options['data']->getProgressiveProfilingLimit() : '',
            ]
        );

        // Render style for new form by default
        if (null === $options['data']->getId()) {
            $options['data']->setRenderStyle(true);
        }

        $builder->add('renderStyle', YesNoButtonGroupType::class, [
            'label'      => 'mautic.form.form.renderstyle',
            'data'       => (null === $options['data']->getRenderStyle()) ? true : $options['data']->getRenderStyle(),
            'empty_data' => true,
            'attr'       => [
                'tooltip' => 'mautic.form.form.renderstyle.tooltip',
            ],
        ]);

        $builder->add('publishUp', DateTimeType::class, [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishup',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('publishDown', DateTimeType::class, [
            'widget'     => 'single_text',
            'label'      => 'mautic.core.form.publishdown',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'data-toggle' => 'datetime',
            ],
            'format'   => 'yyyy-MM-dd HH:mm',
            'required' => false,
        ]);

        $builder->add('postAction', ChoiceType::class, [
            'choices' => [
                'mautic.form.form.postaction.return'   => 'return',
                'mautic.form.form.postaction.redirect' => 'redirect',
                'mautic.form.form.postaction.message'  => 'message',
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
            'attr'       => ['class' => 'form-control'],
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class'        => Form::class,
            'validation_groups' => [
                Form::class,
                'determineValidationGroups',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'mauticform';
    }
}
