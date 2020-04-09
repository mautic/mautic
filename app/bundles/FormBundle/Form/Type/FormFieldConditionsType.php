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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\ConditionalField\ConditionalFieldFactory;
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
            'enabled',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.field.form.condition.enabled',
                'data'  => isset($options['data']['enabled']) ? $options['data']['enabled'] : false,
            ]
        );

        $conditionalFieldFactory = new ConditionalFieldFactory();
        $fieldsMatchingFactory   = $conditionalFieldFactory->getFieldsMatchingFactory($options['data']['fields'], $options['data']['contactFields']);
        $builder->add(
            'field',
            ChoiceType::class,
            [
                'choices'     => $fieldsMatchingFactory->getChoices(),
                'multiple'    => false,
                'label'       => 'mautic.form.field.form.condition.field.mapping',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'              => 'form-control',
                    'onchange'           => 'Mautic.updateConditionalFieldValues("'.$options['data']['formId'].'", this.value);',
                    'data-field-options' => [],
                    'data-show-on'       => '{"formfield_conditions_enabled_0": ""}',
                ],
                'data'        => isset($options['data']['field']) ? $options['data']['field'] : '',
                'required'    => false,
            ]
        );

        $builder->add(
            'value',
            ChoiceType::class,
            [
                'choices'  => [],
                'multiple' => true,
                'label'    => '',
                'attr'     => [
                    'class'              => 'form-control',
                    'data-show-on'       => '{"formfield_conditions_enabled_0": ""}',
                ],
             //   'data'=> isset($options['data']['value']) ? $options['data']['value'] : '',
                'required' => false,
            ]
        );
    }
}
