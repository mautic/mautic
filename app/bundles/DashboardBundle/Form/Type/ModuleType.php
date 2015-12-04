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
use Mautic\DashboardBundle\Event\ModuleFormEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
            'label'       => 'mautic.dashboard.module.form.type',
            'choices'     => $event->getTypes(),
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array('class' => 'form-control'),
            'empty_value' => 'mautic.core.select',
            'attr'        => array(
                'class'     => 'form-control',
                'onchange'  => 'Mautic.updateModuleForm(this)'
            )
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
                '215' => '215px',
                '330' => '330px',
                '445' => '445px',
                '560' => '560px',
                '675' => '675px',
            ),
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $ff = $builder->getFormFactory();

        // function to add a form for specific module type dynamically
        $func = function (FormEvent $e) use ($ff, $dispatcher) {
            $data    = $e->getData();
            $form    = $e->getForm();
            $event   = new ModuleFormEvent();
            $type    = null;
            $params  = array();

            // $data is object on load, array on save (??)
            if (is_array($data)) {
                if (isset($data['type'])) {
                    $type = $data['type'];
                }
                if (isset($data['params'])) {
                    $params = $data['params'];
                }
            } else {
                $type = $data->getType();
                $params = $data->getParams();
            }

            $event->setType($type);
            $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE, $event);
            $moduleForm = $event->getForm();

            if (isset($moduleForm['formAlias']))
            $form->add('params', $moduleForm['formAlias'], array(
                'label' => false,
                'data'  => $params
            ));
        };

        $builder->add('id', 'hidden');

        $builder->add('buttons', 'form_buttons', array(
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_BIND, $func);
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "module";
    }
}