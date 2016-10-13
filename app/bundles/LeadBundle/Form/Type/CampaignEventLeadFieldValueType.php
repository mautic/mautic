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

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignEventLeadFieldValueType.
 */
class CampaignEventLeadFieldValueType extends AbstractType
{
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'field',
            'leadfields_choices',
            [
                'label'       => 'mautic.lead.campaign.event.field',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => false,
                'with_tags'   => true,
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.lead.campaign.event.field_descr',
                    'onchange' => 'Mautic.updateLeadFieldValues(this)',
                ],
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.core.value.required']
                    ),
                ],
            ]
        );

        $leadModel  = $this->factory->getModel('lead.lead');
        $fieldModel = $this->factory->getModel('lead.field');
        $operators  = $leadModel->getFilterExpressionFunctions();
        $choices    = [];

        foreach ($operators as $key => $operator) {
            $choices[$key] = $operator['label'];
        }

        $builder->add(
            'operator',
            'choice',
            [
                'choices' => $choices,
            ]
        );

        $ff = $builder->getFormFactory();

        // function to add 'template' choice field dynamically
        $func = function (FormEvent $e) use ($ff, $fieldModel) {
            $data = $e->getData();
            $form = $e->getForm();

            $fieldValues = null;
            $fieldType   = null;
            $choiceTypes = ['boolean', 'locale', 'country', 'region', 'lookup', 'timezone', 'select', 'radio'];

            if (isset($data['field'])) {
                $field = $fieldModel->getRepository()->findOneBy(['alias' => $data['field']]);

                if ($field) {
                    $properties = $field->getProperties();
                    $fieldType  = $field->getType();
                    if (!empty($properties['list'])) {
                        // Lookup/Select options
                        $fieldValues = FormFieldHelper::parseList($properties['list']);
                    } elseif (!empty($properties) && $fieldType == 'boolean') {
                        // Boolean options
                        $fieldValues = [
                            0 => $properties['no'],
                            1 => $properties['yes'],
                        ];
                    } else {
                        switch ($fieldType) {
                            case 'country':
                                $fieldValues = FormFieldHelper::getCountryChoices();
                                break;
                            case 'region':
                                $fieldValues = FormFieldHelper::getRegionChoices();
                                break;
                            case 'timezone':
                                $fieldValues = FormFieldHelper::getTimezonesChoices();
                                break;
                            case 'locale':
                                $fieldValues = FormFieldHelper::getLocaleChoices();
                                break;
                            default:
                                if (!empty($properties)) {
                                    $fieldValues = $properties;
                                }
                        }
                    }
                }
            }

            // Display selectbox for a field with choices, textbox for others
            if (!empty($fieldValues) && in_array($fieldType, $choiceTypes)) {
                $form->add(
                    'value',
                    'choice',
                    [
                        'choices'    => $fieldValues,
                        'label'      => 'mautic.form.field.form.value',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                    ]
                );
            } else {
                $form->add(
                    'value',
                    'text',
                    [
                        'label'      => 'mautic.form.field.form.value',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required'    => true,
                        'constraints' => [
                            new NotBlank(
                                ['message' => 'mautic.core.value.required']
                            ),
                        ],
                    ]
                );
            }
        };

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'campaignevent_lead_field_value';
    }
}
