<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\LeadBundle\Form\DataTransformer\FieldToOrderTransformer;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\LeadBundle\Helper\FormFieldHelper;

/**
 * Class FieldType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FieldType extends AbstractType
{

    private $container;
    private $em;

    /**
     * @param Container     $container
     * @param EntityManager $em
     */
    public function __construct(Container $container, EntityManager $em) {
        $this->container = $container;
        $this->em        = $em;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());

        $builder->add('label', 'text', array(
            'label'      => 'mautic.lead.field.form.label',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'length' => 50)
        ));

        $disabled = (!empty($options['data'])) ? $options['data']->isFixed() : false;
        $builder->add('alias', 'text', array(
            'label'      => 'mautic.lead.field.form.alias',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'length'  => 25,
                'tooltip' => 'mautic.lead.field.help.alias',
            ),
            'required'   => false,
            'disabled'   => $disabled
        ));

        $builder->add('type', 'choice', array(
            'choices'     => FormFieldHelper::getChoiceList(),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.lead.field.form.type',
            'empty_value' => false,
            'disabled'    => $disabled,
            'attr'        => array(
                'class'    => 'form-control',
                'onchange' => 'Mautic.updateLeadFieldProperties(this.value);'
            ),
        ));

        $builder->add('properties', 'collection', array(
            'required'        => false,
            'allow_add'       => true,
            'error_bubbling'  => false
        ));

        $builder->add('defaultValue', 'text', array(
            'label'      => 'mautic.lead.field.form.defaultvalue',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        //get order list
        $transformer = new FieldToOrderTransformer($this->em);
        $builder->add(
            $builder->create('order', 'entity', array(
                'class'         => 'MauticLeadBundle:LeadField',
                'property'      => 'label',
                'label_attr'    => array('class' => 'control-label'),
                'attr'          => array('class' => 'form-control'),
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('f')
                        ->orderBy('f.order', 'ASC');
                }
            ))->addModelTransformer($transformer)
        );

        $builder->add('isRequired', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.lead.field.form.isrequired',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('isVisible', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.lead.field.form.isvisible',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('isListable', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.lead.field.form.islistable',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('save', 'submit', array(
            'label' => 'mautic.core.form.save',
            'attr'  => array(
                'class' => 'btn btn-primary',
                'icon'  => 'fa fa-check padding-sm-right'
            ),
        ));

        $builder->add('cancel', 'submit', array(
            'label' => 'mautic.core.form.cancel',
            'attr'  => array(
                'class'   => 'btn btn-danger',
                'icon'    => 'fa fa-times padding-sm-right'
            )
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\LeadBundle\Entity\LeadField'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "leadfield";
    }
}