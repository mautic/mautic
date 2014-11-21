<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Form\DataTransformer\FieldToOrderTransformer;
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

    private $translator;
    private $em;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->em         = $factory->getEntityManager();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('lead.field', $options));

        $builder->add('label', 'text', array(
            'label'      => 'mautic.lead.field.form.label',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control', 'length' => 50)
        ));

        $disabled = (!empty($options['data'])) ? $options['data']->isFixed() : false;

        $builder->add('group', 'choice', array(
            'choices'       => array(
                'core'         => 'mautic.lead.field.group.core',
                'social'       => 'mautic.lead.field.group.social',
                'personal'     => 'mautic.lead.field.group.personal',
                'professional' => 'mautic.lead.field.group.professional'
            ),
            'attr'        => array(
                'class'    => 'form-control',
                'tooltip'  => 'mautic.lead.field.form.group.help'
            ),
            'expanded'      => false,
            'multiple'      => false,
            'label'         => 'mautic.lead.field.form.group',
            'empty_value'   => false,
            'required'      => false,
            'disabled'      => $disabled
        ));

        $new      = (!empty($options['data']) && $options['data']->getAlias()) ? false : true;
        $default  = ($new) ? 'text' : $options['data']->getType();
        $fieldHelper = new FormFieldHelper();
        $fieldHelper->setTranslator($this->translator);
        $builder->add('type', 'choice', array(
            'choices'     => $fieldHelper->getChoiceList(),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.lead.field.form.type',
            'empty_value' => false,
            'disabled'    => ($disabled || !$new),
            'attr'        => array(
                'class'    => 'form-control',
                'onchange' => 'Mautic.updateLeadFieldProperties(this.value);'
            ),
            'data'        => $default
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
                'label'         => 'mautic.lead.field.form.order',
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

        $builder->add('alias', 'text', array(
            'label'      => 'mautic.lead.field.form.alias',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'length'  => 25,
                'tooltip' => 'mautic.lead.field.help.alias',
            ),
            'required'   => false,
            'disabled'   => ($disabled || !$new)
        ));

        $data = ($disabled) ? true : $options['data']->getIsPublished();
        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.core.form.ispublished',
            'empty_value'   => false,
            'required'      => false,
            'disabled'      => $disabled,
            'data'          => $data
        ));

        $builder->add('isRequired', 'button_group', array(
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

        $builder->add('isVisible', 'button_group', array(
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

        $builder->add('isShortVisible', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'multiple'      => false,
            'label'         => 'mautic.lead.field.form.isshortvisible',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('isListable', 'button_group', array(
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

        $builder->add('buttons', 'form_buttons');

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
