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
     * @param MauticFactory       $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory    = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        foreach ($options['lead_fields'] as $field => $label) {
            $builder->add($field, 'choice', array(
                'choices'    => $options['import_fields'],
                'label'      => $label,
                'required'   => false,
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control')
            ));
        }

        $properties = $builder->create('properties', 'form', array('virtual' => true));

        $properties->add('dateAdded', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'dateAdded',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('createdByUser', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'createdByUser',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('dateModified', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'dateModified',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('modifiedByUser', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'modifiedByUser',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('lastActive', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'lastActive',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('dateIdentified', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'dateIdentified',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('ip', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'ip',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('points', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'points',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $properties->add('doNotEmail', 'choice', array(
            'choices'    => $options['import_fields'],
            'label'      => 'doNotEmail',
            'required'   => false,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add($properties);

        $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create('owner', 'user_list', array(
                'label'      => 'mautic.lead.lead.field.owner',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control'
                ),
                'required'   => false,
                'multiple'   => false
            ))
                ->addModelTransformer($transformer)
        );

        $builder->add(
            $builder->create('list', 'leadlist_choices', array(
                'label'      => 'mautic.lead.lead.field.list',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control'
                ),
                'required'   => false,
                'multiple'   => false
            ))
        );

        $builder->add('buttons', 'form_buttons', array(
            'apply_text'   => false,
            'save_text'    => 'mautic.lead.import.start',
            'save_class'   => 'btn btn-danger',
            'save_icon'    => 'fa fa-user-plus',
            'cancel_icon'  => 'fa fa-times'
        ));

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
    public function getName() {
        return "lead_field_import";
    }
}
