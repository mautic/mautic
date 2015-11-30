<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\ModuleTypeListEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ModuleType
 *
 * @package Mautic\DashboardBundle\Form\Type
 */
class ModuleType extends AbstractType
{

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', array(
            'label'      => 'mautic.dashboard.module.form.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $dispatcher = $this->factory->getDispatcher();
        $event      = new ModuleTypeListEvent();
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE, $event);

        $builder->add('type', 'choice', array(
            'label'      => 'mautic.dashboard.module.form.type',
            'choices'    => $event->getTypes(),
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('width', 'choice', array(
            'label'      => 'mautic.dashboard.module.form.width',
            'choices'    => array(
                '3' => '25%',
                '4' => '33%',
                '6' => '50%',
                '8' => '67%',
                '9' => '75%',
                '12' => '100%',
            ),
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('height', 'choice', array(
            'label'      => 'mautic.dashboard.module.form.height',
            'choices'    => array(
                '100' => '100px',
                '200' => '200px',
                '300' => '300px',
                '400' => '400px',
                '500' => '500px',
                '600' => '600px',
            ),
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        // @todo load list of modules here
        $insertBefore = array(
            0 => 'mautic.dashboard.module.ordering.last'
        );

        $builder->add('ordering', 'choice', array(
            'label'      => 'mautic.dashboard.module.form.ordering',
            'choices'    => $insertBefore,
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('buttons', 'form_buttons', array(
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    // public function setDefaultOptions (OptionsResolverInterface $resolver)
    // {
    //     $resolver->setDefaults(array(
    //         'data_class' => 'Mautic\LeadBundle\Entity\LeadNote'
    //     ));
    // }

    /**
     * @return string
     */
    public function getName ()
    {
        return "module";
    }
}