<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\EmailBundle\Form\Type\EmailUtmTagsType;
use Mautic\FormBundle\Form\Type\FormListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FocusType extends AbstractType
{
    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * FocusType constructor.
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['website' => 'url', 'html' => 'html', 'editor' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('focus', $options));

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            TextareaType::class,
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'utmTags',
            EmailUtmTagsType::class,
            [
                'label'      => 'mautic.email.utm_tags',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.utm_tags.tooltip',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'html_mode',
            ButtonGroupType::class,
            [
                'label'      => 'mautic.focus.form.html_mode',
                'label_attr' => ['class' => 'control-label'],
                'data'       => !empty($options['data']->getHtmlMode()) ? $options['data']->getHtmlMode() : 'basic',
                'attr'       => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.focusUpdatePreview()',
                    'tooltip'  => 'mautic.focums.html_mode.tooltip',
                ],
                'choices' => [
                    'mautic.focus.form.basic'  => 'basic',
                    'mautic.focus.form.editor' => 'editor',
                    'mautic.focus.form.html'   => 'html',
                ],
            ]
        );

        $builder->add(
            'editor',
            TextareaType::class,
            [
                'label'      => 'mautic.focus.form.editor',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control editor editor-basic',
                    'data-show-on' => '{"focus_html_mode_1":"checked"}',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'html',
            TextareaType::class,
            [
                'label'      => 'mautic.focus.form.html',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'rows'         => 12,
                    'data-show-on' => '{"focus_html_mode_2":"checked"}',
                    'onchange'     => 'Mautic.focusUpdatePreview()',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'website',
            UrlType::class,
            [
                'label'      => 'mautic.focus.form.website',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.focus.form.website.tooltip',
                ],
                'required' => false,
            ]
        );

        //add category
        $builder->add(
            'category',
            CategoryListType::class,
            [
                'bundle' => 'plugin:focus',
            ]
        );

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->isGranted('focus:items:publish');
            $data     = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('focus:items:publish')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = false;
        }

        $builder->add(
            'isPublished',
            YesNoButtonGroupType::class,
            [
                'data' => $data,
                'attr' => [
                    'readonly' => $readonly,
                ],
            ]
        );

        $builder->add(
            'publishUp',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishup',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add(
            'publishDown',
            DateTimeType::class,
            [
                'widget'     => 'single_text',
                'label'      => 'mautic.core.form.publishdown',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'data-toggle' => 'datetime',
                ],
                'format'   => 'yyyy-MM-dd HH:mm',
                'required' => false,
            ]
        );

        $builder->add('properties', PropertiesType::class, ['data' => $options['data']->getProperties()]);

        // Will be managed by JS
        $builder->add('type', HiddenType::class);
        $builder->add('style', HiddenType::class);

        $builder->add(
            'form',
            FormListType::class,
            [
                'label'       => 'mautic.focus.form.choose_form',
                'multiple'    => false,
                'placeholder' => '',
                'attr'        => [
                    'onchange' => 'Mautic.focusUpdatePreview()',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $customButtons = [
            [
                'name'  => 'builder',
                'label' => 'mautic.core.builder',
                'attr'  => [
                    'class'   => 'btn btn-default btn-dnd btn-nospin',
                    'icon'    => 'fa fa-cube',
                    'onclick' => 'Mautic.launchFocusBuilder();',
                ],
            ],
        ];

        if (!empty($options['update_select'])) {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'apply_text'        => false,
                    'pre_extra_buttons' => $customButtons,
                ]
            );
            $builder->add(
                'updateSelect',
                HiddenType::class,
                [
                    'data'   => $options['update_select'],
                    'mapped' => false,
                ]
            );
        } else {
            $builder->add(
                'buttons',
                FormButtonsType::class,
                [
                    'pre_extra_buttons' => $customButtons,
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'MauticPlugin\MauticFocusBundle\Entity\Focus',
            ]
        );
        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'focus';
    }
}
