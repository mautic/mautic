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

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FocusType extends AbstractType
{
    /**
     * @var CorePermissions
     */
    private $security;

    /**
     * FocusType constructor.
     *
     * @param CorePermissions $security
     */
    public function __construct(CorePermissions $security)
    {
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(['website' => 'url', 'html' => 'html']));
        $builder->addEventSubscriber(new FormExitSubscriber('focus', $options));

        $builder->add(
            'name',
            'text',
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
            ]
        );

        $builder->add(
            'description',
            'textarea',
            [
                'label'      => 'mautic.core.description',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control editor'],
                'required'   => false,
            ]
        );

        $builder->add(
            'utmTags',
            'utm_tags',
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

        $builder->add('html_mode', 'button_group', [
            'label'      => 'mautic.focus.form.html_mode',
            'label_attr' => ['class' => 'control-label'],
            'data'       => !empty($options['data']->getHtmlMode()) ? $options['data']->getHtmlMode() : 'basic',
            'attr'       => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.focusUpdatePreview()',
            ],
            'choice_list' => new ChoiceList(
                ['basic', 'editor', 'html'],
                ['mautic.focus.form.basic', 'mautic.focus.form.editor', 'mautic.focus.form.html']
            ),
        ]);

        $builder->add(
            'editor',
            'textarea',
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
            'textarea',
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
            'url',
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
            'category',
            [
                'bundle' => 'plugin:focus',
            ]
        );

        if (!empty($options['data']) && $options['data']->getId()) {
            $readonly = !$this->security->isGranted('plugin:focus:items:publish');
            $data     = $options['data']->isPublished(false);
        } elseif (!$this->security->isGranted('plugin:focus:items:publish')) {
            $readonly = true;
            $data     = false;
        } else {
            $readonly = false;
            $data     = false;
        }

        $builder->add(
            'isPublished',
            'yesno_button_group',
            [
                'read_only' => $readonly,
                'data'      => $data,
            ]
        );

        $builder->add(
            'publishUp',
            'datetime',
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
            'datetime',
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

        $builder->add('properties', 'focus_entity_properties', ['data' => $options['data']->getProperties()]);

        // Will be managed by JS
        $builder->add('type', 'hidden');
        $builder->add('style', 'hidden');

        $builder->add(
            'form',
            'form_list',
            [
                'label'       => 'mautic.focus.form.choose_form',
                'multiple'    => false,
                'empty_value' => '',
                'attr'        => [
                    'onchange'     => 'Mautic.focusUpdatePreview()',
                    'data-show-on' => '{"focus_html_mode_1":""}',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        $builder->add(
            'buttons',
            'form_buttons',
            [
                'pre_extra_buttons' => [
                    [
                        'name'  => 'builder',
                        'label' => 'mautic.core.builder',
                        'attr'  => [
                            'class'   => 'btn btn-default btn-dnd btn-nospin',
                            'icon'    => 'fa fa-cube',
                            'onclick' => 'Mautic.launchFocusBuilder();',
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'MauticPlugin\MauticFocusBundle\Entity\Focus',
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'focus';
    }
}
