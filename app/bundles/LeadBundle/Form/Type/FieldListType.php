<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldListType extends AbstractType
{
    public function __construct(private FieldModel $fieldModel)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $leadFields = $this->fieldModel->getLeadFields();
        $choices    = [];
        foreach ($leadFields as $field) {
            $choices[$field->getLabel()] = $field->getId();
        }

        $resolver->setDefaults(
            [
                'label'             => 'mautic.lead.field.listType.label',
                'label_attr'        => ['class' => 'control-label'],
                'multiple'          => true,
                'placeholder'       => 'mautic.core.form.uncategorized',
                'attr'              => [
                    'class' => 'form-control',
                ],
                'choices' => $choices,
            ]
        );
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
