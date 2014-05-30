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
use Mautic\Corebundle\Form\DataTransformer\CleanTransformer;
use Mautic\LeadBundle\Form\DataTransformer\FieldTypeTransformer;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ListType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class ListType extends AbstractType
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
        $cleanTransformer = new CleanTransformer();

        $builder->add(
            $builder->create('name', 'text', array(
                'label'      => 'mautic.lead.list.form.name',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control', 'length' => 50)
            ))->addViewTransformer($cleanTransformer)
        );

        $builder->add(
            $builder->create('alias', 'text', array(
                'label'      => 'mautic.lead.list.form.alias',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array(
                    'class'   => 'form-control',
                    'length'  => 25,
                    'tooltip' => 'mautic.lead.list.help.alias'
                ),
                'required'   => false
            ))->addViewTransformer($cleanTransformer)
        );

        $builder->add(
            $builder->create('description', 'text', array(
                'label'      => 'mautic.lead.list.form.description',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => false
            ))->addViewTransformer($cleanTransformer)
        );

        $builder->add('isGlobal', 'choice', array(
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

        $builder->add('isActive', 'choice', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'expanded'      => true,
            'label_attr'    => array('class' => 'control-label'),
            'multiple'      => false,
            'label'         => 'mautic.core.form.isactive',
            'empty_value'   => false,
            'required'      => false
        ));

        $filterTransformer = new FieldTypeTransformer();
        $builder->add(
            $builder->create('filters', 'leadlist_filters', array(
                'error_bubbling' => false,
                'mapped'         => true
            ))
                ->addViewTransformer($filterTransformer)
        );

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