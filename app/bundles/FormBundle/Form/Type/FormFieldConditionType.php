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
use Mautic\FormBundle\ConditionalField\Enum\ConditionalFieldEnum;
use Mautic\FormBundle\ConditionalField\PropertiesProcessor;
use Mautic\FormBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormFieldConditionType extends AbstractType
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var PropertiesProcessor
     */
    private $propertiesProcessor;

    /**
     * FormFieldConditionType constructor.
     *
     * @param FieldModel          $fieldModel
     * @param PropertiesProcessor $propertiesProcessor
     */
    public function __construct(FieldModel $fieldModel, PropertiesProcessor $propertiesProcessor)
    {
        $this->fieldModel          = $fieldModel;
        $this->propertiesProcessor = $propertiesProcessor;
    }

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

        $selectedField = isset($options['data']['field']) ? $options['data']['field'] : '';

        $choices = $this->propertiesProcessor->getFieldsChoices(
            $this->getFieldsForConditions($options['formId'], $options['fieldAlias'])
        );
        $builder->add(
            'field',
            ChoiceType::class,
            [
                'choices'     => $choices,
                'multiple'    => false,
                'label'       => 'mautic.form.field.form.condition.field.mapping',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'              => 'form-control',
                    'onchange'           => 'Mautic.updateConditionalFieldValues(this.value);',
                    'data-field-options' => [],
                    'data-show-on'       => '{"formfield_conditions_enabled_0": ""}',
                ],
                'data'        => $selectedField,
                'required'    => false,
            ]
        );

        $field   = $this->fieldModel->getRepository()->findOneBySlugs($selectedField);
        $choices = $field ? $this->propertiesProcessor->getFieldPropertiesChoices($field) : [];
        $builder->add(
            'values',
            ChoiceType::class,
            [
                'choices'  => $choices,
                'multiple' => true,
                'label'    => '',
                'attr'     => [
                    'class'              => 'form-control',
                    'data-show-on'       => '{"formfield_conditions_enabled_0": ""}',
                ],
                'data'     => isset($options['data']['values']) ? $options['data']['values'] : [],
                'required' => false,
            ]
        );
    }

    /**
     * @param int    $formId
     * @param string $fieldAlias
     *
     * @return array
     */
    private function getFieldsForConditions($formId, $fieldAlias)
    {
        $fields = $this->fieldModel->getSessionFields($formId);
        foreach ($fields as $key => $field) {
            if (!in_array($field['type'], ConditionalFieldEnum::$conditionalFieldTypes)) {
                unset($fields[$key]);
            } elseif ($field['alias'] === $fieldAlias) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'formId'     => null,
                'fieldAlias' => null,
            ]
        );
    }
}
