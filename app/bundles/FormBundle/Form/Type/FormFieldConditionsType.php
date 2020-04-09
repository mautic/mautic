<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class FormFieldConditionsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'dependentLabel',
            ChoiceType::class,
            [
                'choices'     => [],
                'multiple'    => false,
                'label'       => 'mautic.form.field.form.conditions.field.mapping',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'              => 'form-control',
                    'onchange'           => 'Mautic.dependentupdateFormFieldValues(this);',
                    'data-field-options' => [],
                ],
                'required'    => false,
            ]
        );

        $builder->add(
            'dependentValue',
            ChoiceType::class,
            [
                'choices'  => [],
                'multiple' => true,
                'label'    => '',
                'attr'     => [
                    'class'        => 'form-control',
                ],
                'required' => false,
            ]
        );
    }
}
