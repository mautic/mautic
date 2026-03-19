<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\CompanySegmentModel;
use Mautic\LeadBundle\Provider\FormAdjustmentsProviderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanySegmentFilterType extends AbstractType
{
    public function __construct(
        private FormAdjustmentsProviderInterface $formAdjustmentsProvider,
        private CompanySegmentModel $companySegmentModel,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldChoices = $this->companySegmentModel->getChoiceFields();

        $builder->add(
            'glue',
            ChoiceType::class,
            [
                'label'   => false,
                'choices' => [
                    'mautic.lead.list.form.glue.and' => 'and',
                    'mautic.lead.list.form.glue.or'  => 'or',
                ],
                'attr' => [
                    'class'    => 'label label-warm-gray not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)',
                ],
            ]
        );

        $formModifier = function (FormEvent $event) use ($fieldChoices): void {
            /** @var array<string, mixed> $data */
            $data           = (array) $event->getData();
            $form           = $event->getForm();
            $fieldAliasRaw  = $data['field'] ?? null;
            $fieldObjectRaw = $data['object'] ?? 'behaviors';
            $fieldAlias     = null;
            if (is_string($fieldAliasRaw) || is_int($fieldAliasRaw) || is_float($fieldAliasRaw)) {
                $fieldAlias = (string) $fieldAliasRaw;
            }
            $fieldObject = 'behaviors';
            if (is_string($fieldObjectRaw) || is_int($fieldObjectRaw) || is_float($fieldObjectRaw)) {
                $fieldObject = (string) $fieldObjectRaw;
            }
            // Looking for behaviors for BC reasons as some filters were moved from 'lead' to 'behaviors'.
            $field = null;
            if (null !== $fieldAlias) {
                if (isset($fieldChoices[$fieldObject][$fieldAlias])) {
                    $field = $fieldChoices[$fieldObject][$fieldAlias];
                } elseif (isset($fieldChoices['behaviors'][$fieldAlias])) {
                    $field = $fieldChoices['behaviors'][$fieldAlias];
                }
            }

            $operators = [];
            if (is_array($field) && is_array($field['operators'])) {
                $operators = $field['operators'];
            }

            $operator = $data['operator'] ?? null;

            if ([] !== $operators && (null === $operator || '' === $operator)) {
                $operator = array_key_first($operators);
            }

            $form->add(
                'operator',
                ChoiceType::class,
                [
                    'label'   => false,
                    'choices' => $operators,
                    'attr'    => [
                        'class'    => 'form-control not-chosen',
                        'onchange' => 'Mautic.convertCompanySegmentFilterInput(this)',
                    ],
                ]
            );

            $form->add(
                'properties',
                FilterPropertiesType::class,
                [
                    'label' => false,
                ]
            );

            if (!is_array($field)) {
                // The field was probably deleted since the segment was created.
                // Do not show up the filter based on a deleted field.
                return;
            }

            $filterPropertiesType = $form->get('properties');
            $filterPropertiesType->setData($data['properties'] ?? []);

            if (
                null !== $fieldAlias
                && '' !== $fieldAlias
                && null !== $operator
                && '' !== $operator
                && is_string($operator)
            ) {
                $this->formAdjustmentsProvider->adjustForm(
                    $filterPropertiesType,
                    $fieldAlias,
                    $fieldObject,
                    $operator,
                    $field
                );
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $formModifier);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $formModifier);
        $builder->add('field', HiddenType::class);
        $builder->add('object', HiddenType::class);
        $builder->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var array<string, mixed> $vars */
        $vars           = $view->vars;
        $vars['fields'] = $this->companySegmentModel->getChoiceFields();
        $view->vars     = $vars;
    }

    public function getBlockPrefix(): string
    {
        return 'leadlist_filter';
    }
}
