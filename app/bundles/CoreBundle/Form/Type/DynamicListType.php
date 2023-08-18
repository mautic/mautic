<?php

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class DynamicListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();

                // Reorder list in case keys were dynamically removed.
                if (is_array($data)) {
                    $data = array_values($data);
                    $event->setData($data);
                }
            }
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['isSortable'] = (!empty($options['sortable']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'remove_onclick'  => 'Mautic.removeFormListOption(this);',
                'option_required' => true,
                'option_notblank' => true,
                'remove_icon'     => 'fa fa-times',
                'sortable'        => 'fa fa-ellipsis-v handle',
                'label'           => false,
                'entry_options'   => [
                    'label'    => false,
                    'required' => false,
                    'attr'     => [
                        'class'         => 'form-control',
                        'preaddon'      => function (Options $options) {
                            return $options['remove_icon'];
                        },
                        'preaddon_attr' => function (Options $options) {
                            return [
                                'onclick' => $options['remove_onclick'],
                            ];
                        },
                        'postaddon'     => function (Options $options) {
                            return $options['sortable'];
                        },
                    ],

                    'constraints'    => function (Options $options) {
                        return ($options['option_notblank']) ? [
                            new NotBlank(
                                ['message' => 'mautic.form.lists.notblank']
                            ),
                        ] : [];
                    },
                    'error_bubbling' => true,
                ],
                'allow_add'       => true,
                'allow_delete'    => true,
                'prototype'       => true,
                'constraints'     => function (Options $options) {
                    return ($options['option_required']) ? [
                        new Count(
                            [
                                'minMessage' => 'mautic.form.lists.count',
                                'min'        => 1,
                            ]
                        ),
                    ] : [];
                },
                'error_bubbling'  => false,
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

    public function getBlockPrefix()
    {
        return 'dynamiclist';
    }

    public function getParent()
    {
        return CollectionType::class;
    }
}
