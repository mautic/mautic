<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
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

class FilterType extends AbstractType
{
    use FilterTrait;

    public function __construct(
        private FormAdjustmentsProviderInterface $formAdjustmentsProvider,
        private ListModel $listModel
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fieldChoices = $this->listModel->getChoiceFields();

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
                    'class'    => 'form-control not-chosen glue-select',
                    'onchange' => 'Mautic.updateFilterPositioning(this)',
                ],
            ]
        );

        $formModifier = function (FormEvent $event) use ($fieldChoices): void {
            $data        = (array) $event->getData();
            $form        = $event->getForm();
            $fieldAlias  = $data['field'] ?? null;
            $fieldObject = $data['object'] ?? 'behaviors';
            // Looking for behaviors for BC reasons as some filters were moved from 'lead' to 'behaviors'.
            $field       = $fieldChoices[$fieldObject][$fieldAlias] ?? $fieldChoices['behaviors'][$fieldAlias] ?? null;
            $operators   = $field['operators'] ?? [];
            $operator    = $data['operator'] ?? null;

            if ($operators && !$operator) {
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
                        'onchange' => 'Mautic.convertLeadFilterInput(this)',
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

            if (null === $field) {
                // The field was probably deleted since the segment was created.
                // Do not show up the filter based on a deleted field.
                return;
            }

            $filterPropertiesType = $form->get('properties');

            $this->setPropertiesFormData($filterPropertiesType, $data);

            if ($fieldAlias && $operator) {
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
        $view->vars['fields'] = $this->listModel->getChoiceFields();
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_filter';
    }

    /**
     * We have to ensure that the old data[filter] and data[display] will get to the properties form
     * to keep BC for segments created before the properties form was added and the fitler and display
     * fields were moved there.
     *
     * @param FormInterface<FormInterface> $filterPropertiesType
     * @param mixed[]                      $data
     */
    private function setPropertiesFormData(FormInterface $filterPropertiesType, array $data): void
    {
        if (empty($data['properties'])) {
            $propertiesData = [
                'filter'  => $data['filter'] ?? null,
                'display' => $data['display'] ?? null,
            ];
            $filterPropertiesType->setData($propertiesData);
        } else {
            $filterPropertiesType->setData($data['properties'] ?? []);
        }
    }
}
