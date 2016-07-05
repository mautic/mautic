<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadImportFieldType
 *
 * @package Mautic\LeadBundle\Form\Type
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
        foreach ($options['lead_fields'] as $field => $label) {
            $builder->add(
                $field,
                'choice',
                array(
                    'choices'    => $options['import_fields'],
                    'label'      => $label,
                    'required'   => false,
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array('class' => 'form-control'),
                    'data'       => $this->getDefaultValue($field, $options['import_fields'])
                )
            );
        }

        $properties = $builder->create('properties', 'form', array('virtual' => true));

        $properties->add(
            'dateAdded',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateAdded',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('dateAdded', $options['import_fields'])
            )
        );

        $properties->add(
            'createdByUser',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.createdByUser',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('createdByUser', $options['import_fields'])
            )
        );

        $properties->add(
            'dateModified',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateModified',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('dateModified', $options['import_fields'])
            )
        );

        $properties->add(
            'modifiedByUser',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.modifiedByUser',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('modifiedByUser', $options['import_fields'])
            )
        );

        $properties->add(
            'lastActive',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.lastActive',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('lastActive', $options['import_fields'])
            )
        );

        $properties->add(
            'dateIdentified',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.dateIdentified',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('dateIdentified', $options['import_fields'])
            )
        );

        $properties->add(
            'ip',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.ip',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('ip', $options['import_fields'])
            )
        );

        $properties->add(
            'points',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.points',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('points', $options['import_fields'])
            )
        );

        $properties->add(
            'stage',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.stage',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('stage', $options['import_fields'])
            )
        );

        $properties->add(
            'doNotEmail',
            'choice',
            array(
                'choices'    => $options['import_fields'],
                'label'      => 'mautic.lead.import.label.doNotEmail',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'data'       => $this->getDefaultValue('doNotEmail', $options['import_fields'])
            )
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
                array(
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'required'   => false,
                    'multiple'   => false
                )
            )
                ->addModelTransformer($transformer)
        );

        $builder->add(
            $builder->create(
                'list',
                'leadlist_choices',
                array(
                    'label'      => 'mautic.lead.lead.field.list',
                    'label_attr' => array('class' => 'control-label'),
                    'attr'       => array(
                        'class' => 'form-control'
                    ),
                    'required'   => false,
                    'multiple'   => false
                )
            )
        );

        $builder->add(
            'tags',
            'lead_tag',
            array(
                'label'      => 'mautic.lead.tags',
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'                => 'form-control',
                    'data-placeholder'     => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                    'data-no-results-text' => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                    'data-allow-add'       => 'true',
                    'onchange'             => 'Mautic.createLeadTag(this)'
                )
            )
        );

        $builder->add(
            'buttons',
            'form_buttons',
            array(
                'apply_text'  => false,
                'save_text'   => 'mautic.lead.import.start',
                'save_class'  => 'btn btn-primary',
                'save_icon'   => 'fa fa-user-plus',
                'cancel_icon' => 'fa fa-times'
            )
        );

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('lead_fields', 'import_fields'));
    }


    /**
     * @return string
     */
    public function getName()
    {
        return "lead_field_import";
    }

    /**
     * @param  string $fieldName
     * @param  array  $importFields
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
