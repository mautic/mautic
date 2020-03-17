<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Form\Type\FilterPropertiesType;
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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class FilterType extends AbstractType
{
    use FilterTrait;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var FormAdjustmentsProviderInterface
     */
    private $formAdjustmentsProvider;

    /**
     * @var ListModel
     */
    private $listModel;

    public function __construct(
        TranslatorInterface $translator,
        RequestStack $requestStack,
        FormAdjustmentsProviderInterface $formAdjustmentsProvider,
        ListModel $listModel
    ) {
        $this->translator              = $translator;
        $this->requestStack            = $requestStack;
        $this->formAdjustmentsProvider = $formAdjustmentsProvider;
        $this->listModel               = $listModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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

        $formModifier = function (FormEvent $event) use ($fieldChoices) {
            // $segmentId = $this->requestStack->getCurrentRequest()->attributes->get('objectId', false);
            $data        = $event->getData();
            $form        = $event->getForm();
            $fieldAlias  = $data['field'];
            $fieldObject = isset($data['object']) ? $data['object'] : 'behaviors';
            $field       = isset($fieldChoices[$fieldObject][$fieldAlias]) ? $fieldChoices[$fieldObject][$fieldAlias] : [];
            $operators   = $field['operators'] ?? [];
            $operator    = isset($data['operator']) ? $data['operator'] : null;

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

            $filterPropertiesType = $form->get('properties');

            $this->setPropertiesFormData($filterPropertiesType, $data ?? []);

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

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SET_DATA);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($formModifier) {
                $formModifier($event, FormEvents::PRE_SUBMIT);
            }
        );

        $builder->add('field', HiddenType::class);
        $builder->add('object', HiddenType::class);
        $builder->add('type', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'label'          => false,
                'error_bubbling' => false,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
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
     */
    private function setPropertiesFormData(FormInterface $filterPropertiesType, array $data): void
    {
        if (empty($data['properties']) && !empty($data['filter'])) {
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
