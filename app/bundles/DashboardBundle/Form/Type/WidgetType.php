<?php

namespace Mautic\DashboardBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DashboardBundle\DashboardEvents;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Class WidgetType.
 */
class WidgetType extends AbstractType
{
    /**
     * @var ContainerAwareEventDispatcher
     */
    protected $dispatcher;

    /**
     * @var CorePermissions
     */
    protected $security;

    public function __construct(EventDispatcherInterface $dispatcher, CorePermissions $security)
    {
        $this->dispatcher = $dispatcher;
        $this->security   = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            [
                'label'      => 'mautic.dashboard.widget.form.name',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $event = new WidgetTypeListEvent();
        $event->setSecurity($this->security);
        $this->dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_LIST_GENERATE, $event);

        $types = array_map(function ($category) {
            return array_flip($category);
        }, $event->getTypes());

        $builder->add(
            'type',
            ChoiceType::class,
            [
                'label'             => 'mautic.dashboard.widget.form.type',
                'choices'           => $types,
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => 'mautic.core.select',
                'attr'              => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.updateWidgetForm(this)',
                ],
            ]
        );

        $builder->add(
            'width',
            ChoiceType::class,
            [
                'label'   => 'mautic.dashboard.widget.form.width',
                'choices' => [
                    '25%'  => '25',
                    '50%'  => '50',
                    '75%'  => '75',
                    '100%' => '100',
                ],
                'empty_data'        => '100',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );

        $builder->add(
            'height',
            ChoiceType::class,
            [
                'label'   => 'mautic.dashboard.widget.form.height',
                'choices' => [
                    '215px' => '215',
                    '330px' => '330',
                    '445px' => '445',
                    '560px' => '560',
                    '675px' => '675',
                ],
                'empty_data'        => '330',
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );

        // function to add a form for specific widget type dynamically
        $func = function (FormEvent $e) {
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
            $this->dispatcher->dispatch(DashboardEvents::DASHBOARD_ON_MODULE_FORM_GENERATE, $event);
            $widgetForm = $event->getForm();
            $form->setData($params);

            if (isset($widgetForm['formAlias'])) {
                $form->add('params', $widgetForm['formAlias'], [
                    'label' => false,
                ]);
            }
        };

        $builder->add(
            'id',
            HiddenType::class,
            [
                'mapped' => false,
            ]
        );

        $builder->add(
            'buttons',
            FormButtonsType::class,
            [
                'apply_text' => false,
                'save_text'  => 'mautic.core.form.save',
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }

        // Register the function above as EventListener on PreSet and PreBind
        $builder->addEventListener(FormEvents::PRE_SET_DATA, $func);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $func);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'widget';
    }
}
