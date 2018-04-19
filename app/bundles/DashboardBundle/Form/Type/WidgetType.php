<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class WidgetType.
 */
class WidgetType extends AbstractType
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
        $builder->add('name', 'text', [
            'label'      => 'mautic.dashboard.widget.form.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $dispatcher = $this->factory->getDispatcher();
        $event      = new WidgetTypeListEvent();
        $event->setSecurity($this->factory->getSecurity());
        $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE, $event);

        $builder->add('type', 'choice', [
            'label'       => 'mautic.dashboard.widget.form.type',
            'choices'     => $event->getTypes(),
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
            'empty_value' => 'mautic.core.select',
            'attr'        => [
                'class'    => 'form-control',
                'onchange' => 'Mautic.updateWidgetForm(this)',
            ],
        ]);

        $builder->add('width', 'choice', [
            'label'   => 'mautic.dashboard.widget.form.width',
            'choices' => [
                '25'  => '25%',
                '50'  => '50%',
                '75'  => '75%',
                '100' => '100%',
            ],
            'empty_data' => '100',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('height', 'choice', [
            'label'   => 'mautic.dashboard.widget.form.height',
            'choices' => [
                '215' => '215px',
                '330' => '330px',
                '445' => '445px',
                '560' => '560px',
                '675' => '675px',
            ],
            'empty_data' => '330',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        // function to add a form for specific widget type dynamically
        $func = function (FormEvent $e) use ($dispatcher) {
            $data   = $e->getData();
            $form   = $e->getForm();
            $event  = new WidgetFormEvent();
            $type   = null;
            $params = [];

            // $data is object on load, array on save (??)
            if (is_array($data)) {
                if (isset($data['type'])) {
                    $type = $data['type'];
                }
                if (isset($data['params'])) {
                    $params = $data['params'];
                }
            } else {
                $type   = $data->getType();
                $params = $data->getParams();
            }

            $event->setType($type);
            $dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE, $event);
            $widgetForm = $event->getForm();
            $form->setData($params);

            if (isset($widgetForm['formAlias'])) {
                $form->add('params', $widgetForm['formAlias'], [
                    'label' => false,
                ]);
            }
        };

        $builder->add('id', 'hidden', [
            'mapped' => false,
        ]);

        $builder->add('buttons', 'form_buttons', [
            'apply_text' => false,
            'save_text'  => 'mautic.core.form.save',
        ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_BIND, $func);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'widget';
    }
}
