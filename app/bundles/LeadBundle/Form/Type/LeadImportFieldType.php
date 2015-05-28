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
