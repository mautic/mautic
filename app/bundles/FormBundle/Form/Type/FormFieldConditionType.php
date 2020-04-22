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
        $choices = !empty($options['parent']) ? $this->propertiesProcessor->getFieldPropertiesChoicesFromAlias($options['formId'], $options['parent']) : [];
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
