<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\LeadBundle\Form\DataTransformer\FieldDateTimeTransformer;
use Mautic\LeadBundle\Form\DataTransformer\FieldTypeTransformer;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ListType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class ListType extends AbstractType
{

    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber());
        $builder->addEventSubscriber(new FormExitSubscriber('lead.list', $options));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.lead.list.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('alias', 'text', array(
            'label'      => 'mautic.lead.list.form.alias',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'   => 'form-control',
                'length'  => 25,
                'tooltip' => 'mautic.lead.list.help.alias'
            ),
            'required'   => false
        ));

        $builder->add('description', 'text', array(
            'label'      => 'mautic.lead.list.form.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('isGlobal', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'label'         => 'mautic.lead.list.form.isglobal',
            'empty_value'   => false,
            'required'      => false
        ));

        $builder->add('isPublished', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'label'         => 'mautic.core.form.ispublished',
            'empty_value'   => false,
            'required'      => false
        ));

        $filterTransformer      = new FieldTypeTransformer();
        $filterModalTransformer = new FieldDateTimeTransformer();
        $builder->add(
            $builder->create('filters', 'leadlist_filters', array(
                'error_bubbling' => false,
                'mapped'         => true
            ))
                ->addViewTransformer($filterTransformer)
                ->addModelTransformer($filterModalTransformer)
        );

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
            'data_class' => 'Mautic\LeadBundle\Entity\LeadList'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "leadlist";
    }
}