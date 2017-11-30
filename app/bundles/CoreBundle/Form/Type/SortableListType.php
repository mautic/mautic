<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Form\DataTransformer\SortableListTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SortableListType.
 */
class SortableListType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $constraints = ($options['option_required']) ? [
            new Count(
                [
                    'minMessage' => 'mautic.form.lists.count',
                    'min'        => 1,
                ]
            ),
        ] : [];

        if ($options['constraint_callback'] instanceof Callback) {
            $constraints[] = $options['constraint_callback'];
        }

        $builder->add(
            $builder->create(
                'list',
                'collection',
                [
                    'label'      => false,
                    'entry_type' => ($options['with_labels']) ? SortableValueLabelListType::class : $options['entry_type'],
                    'options'    => [
                        'label'    => false,
                        'required' => false,
                        'attr'     => [
                            'class'         => 'form-control',
                            'preaddon'      => $options['remove_icon'],
                            'preaddon_attr' => [
                                'onclick' => $options['remove_onclick'],
                            ],
                            'postaddon' => $options['sortable'],
                        ],
                        'constraints' => ($options['option_notblank']) ? [
                            new NotBlank(
                                ['message' => 'mautic.form.lists.notblank']
                            ),
                        ] : [],
                        'error_bubbling' => true,
                    ],
                    'allow_add'      => true,
                    'allow_delete'   => true,
                    'prototype'      => true,
                    'constraints'    => $constraints,
                    'error_bubbling' => false,
                ]
            )
        )->addModelTransformer(new SortableListTransformer($options['option_notblank'], $options['with_labels']));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['isSortable']     = (!empty($options['sortable']));
        $view->vars['addValueButton'] = $options['add_value_button'];
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'remove_onclick'      => 'Mautic.removeFormListOption(this);',
                'option_required'     => true,
                'option_notblank'     => true,
                'constraint_callback' => false,
                'remove_icon'         => 'fa fa-times',
                'sortable'            => 'fa fa-ellipsis-v handle',
                'with_labels'         => false,
                'entry_type'          => 'text',
                'add_value_button'    => 'mautic.core.form.list.additem',
            ]
        );

        $resolver->setDefined(
            [
                'sortable',
                'remove_onclick',
                'option_required',
                'option_notblank',
                'remove_icon',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sortablelist';
    }
}
