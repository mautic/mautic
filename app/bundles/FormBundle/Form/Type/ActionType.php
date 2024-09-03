<?php

namespace Mautic\FormBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class ActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $masks = ['description' => 'html'];

        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.core.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
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

        $properties      = (!empty($options['data']['properties'])) ? $options['data']['properties'] : null;
        $formType        = $options['settings']['formType'];
        $formTypeOptions = [
            'label' => false,
            'data'  => $properties,
            'attr'  => [
                'data-formid' => $options['formId'], // sneaky way of feeding the formId without requiring the option
            ],
        ];
        if (isset($options['settings']['formTypeCleanMasks'])) {
            $masks['properties'] = $options['settings']['formTypeCleanMasks'];
        }
        if (!empty($options['settings']['formTypeOptions'])) {
            // Ensure that attr is not overwritten
            if (isset($options['settings']['formTypeOptions']['attr'])) {
                $options['settings']['formTypeOptions']['attr']['data-formid'] = $options['formId'];
            }
            $formTypeOptions = array_merge($formTypeOptions, $options['settings']['formTypeOptions']);
        }
        $builder->add('properties', $formType, $formTypeOptions);

        $builder->add('type', HiddenType::class);

        $update = !empty($properties);
        if (!empty($update)) {
            $btnValue = 'mautic.core.form.update';
            $btnIcon  = 'ri-edit-line';
        } else {
            $btnValue = 'mautic.core.form.add';
            $btnIcon  = 'ri-add-line';
        }

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'save_text'       => $btnValue,
                'save_icon'       => $btnIcon,
                'apply_text'      => false,
                'container_class' => 'bottom-form-buttons',
            ]
        );

        $builder->add(
            'formId',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );

        $builder->addEventSubscriber(new CleanFormSubscriber($masks));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['settings', 'formId']);
    }

    public function getBlockPrefix(): string
    {
        return 'formaction';
    }
}
