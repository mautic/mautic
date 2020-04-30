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

use Mautic\FormBundle\Helper\PropertiesAccessor;
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
     * @var PropertiesAccessor
     */
    private $propertiesAccessor;

    /**
     * FormFieldConditionType constructor.
     *
     * @param FieldModel         $fieldModel
     * @param PropertiesAccessor $propertiesAccessor
     */
    public function __construct(FieldModel $fieldModel, PropertiesAccessor $propertiesAccessor)
    {
        $this->fieldModel          = $fieldModel;
        $this->propertiesAccessor  = $propertiesAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        if (!empty($options['parent'])) {
            $fields = $this->fieldModel->getSessionFields($options['formId']);
            if (isset($fields[$options['parent']])) {
                $choices = $this->propertiesAccessor->getChoices($this->propertiesAccessor->getProperties($fields[$options['parent']]));
            }
        }

        $choices = array_merge(['*'=>'mautic.form.field.form.condition.any_value'], $choices);

        $builder->add(
            'values',
            ChoiceType::class,
            [
                'choices'  => $choices,
                'multiple' => true,
                'label'    => '',
                'attr'     => [
                    'class'              => 'form-control',
                ],
                'data'     => isset($options['data']['values']) ? $options['data']['values'] : [],
                'required' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'formId'     => null,
                'parent'     => null,
            ]
        );
    }
}
