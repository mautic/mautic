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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadImportFieldType.
 */
class LeadImportFieldType extends AbstractType
{
    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $importChoiceFields = [
            'mautic.lead.contact' => $options['lead_fields'],
            'mautic.lead.company' => $options['company_fields'],
        ];

        if ($options['object'] !== 'lead') {
            unset($importChoiceFields['mautic.lead.contact']);
        }

        foreach ($options['import_fields'] as $field => $label) {
            $builder->add(
                $field,
                'choice',
                [
                    'choices'    => $importChoiceFields,
                    'label'      => $label,
                    'required'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'data'       => $this->getDefaultValue($field, $options['import_fields']),
                ]
            );
        }

        $properties = $builder->create('properties', 'form', ['virtual' => true]);

        $properties->add(
            'dateAdded',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateAdded',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('dateAdded', $options['import_fields']),
            ]
        );

        $properties->add(
            'createdByUser',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.createdByUser',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('createdByUser', $options['import_fields']),
            ]
        );

        $properties->add(
            'dateModified',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateModified',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('dateModified', $options['import_fields']),
            ]
        );

        $properties->add(
            'modifiedByUser',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.modifiedByUser',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('modifiedByUser', $options['import_fields']),
            ]
        );

        $properties->add(
            'lastActive',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.lastActive',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('lastActive', $options['import_fields']),
            ]
        );

        $properties->add(
            'dateIdentified',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateIdentified',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('dateIdentified', $options['import_fields']),
            ]
        );

        $properties->add(
            'ip',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.ip',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('ip', $options['import_fields']),
            ]
        );

        $properties->add(
            'points',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.points',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('points', $options['import_fields']),
            ]
        );

        $properties->add(
            'stage',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.stage',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('stage', $options['import_fields']),
            ]
        );

        $properties->add(
            'doNotEmail',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.doNotEmail',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('doNotEmail', $options['import_fields']),
            ]
        );

        $properties->add(
            'ownerusername',
            'choice',
            [
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.ownerusername',
                'required'   => false,
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'data'       => $this->getDefaultValue('ownerusername', $options['import_fields']),
            ]
        );

        $builder->add($properties);

        $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                [
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                    'multiple' => false,
                ]
            )
                ->addModelTransformer($transformer)
        );

        if ($options['object'] === 'lead') {
            $builder->add(
                $builder->create(
                    'list',
                    'leadlist_choices',
                    [
                        'label'      => 'mautic.lead.lead.field.list',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required' => false,
                        'multiple' => false,
                    ]
                )
            );

            $builder->add(
                'tags',
                'lead_tag',
                [
                    'label'      => 'mautic.lead.tags',
                    'required'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'                => 'form-control',
                        'data-placeholder'     => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                ]
            );
        }

        $buttons = ['cancel_icon' => 'fa fa-times'];

        if (empty($options['line_count_limit'])) {
            $buttons = array_merge($buttons, [
                'apply_text'  => 'mautic.lead.import.in.background',
                'apply_class' => 'btn btn-success',
                'apply_icon'  => 'fa fa-history',
                'save_text'   => 'mautic.lead.import.start',
                'save_class'  => 'btn btn-primary',
                'save_icon'   => 'fa fa-upload',
            ]);
        } else {
            $buttons = array_merge($buttons, [
                'apply_text' => false,
                'save_text'  => 'mautic.lead.import',
                'save_class' => 'btn btn-primary',
                'save_icon'  => 'fa fa-upload',
            ]);
        }

        $builder->add('buttons', 'form_buttons', $buttons);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['lead_fields', 'import_fields', 'company_fields', 'object']);
        $resolver->setDefaults(['line_count_limit' => 0]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_field_import';
    }

    /**
     * @param string $fieldName
     * @param array  $importFields
     *
     * @return string
     */
    public function getDefaultValue($fieldName, array $importFields)
    {
        if (isset($importFields[$fieldName])) {
            return $importFields[$fieldName];
        }

        return null;
    }
}
