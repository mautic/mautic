<?php

namespace Mautic\DashboardBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\DashboardBundle\Event\GenerateWidgetDetailEvent;
use Mautic\DashboardBundle\Event\PreLoadWidgetDetailEvent;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
use Psr\Cache\InvalidArgumentException;

class DashboardSubscriber
{
    /**
     * Define the name of the bundle/category of the widget(s).
     *
     * @var string
     */
    protected $bundle = 'others';

    /**
     * Define the widget(s).
     *
     * @var array
     */
    protected $types = [];

    /**
     * Define permissions to see those widgets.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * Generates widget preview without data.
     *
     * @throws InvalidArgumentException
     */
    #[AsEventListener(event: PreLoadWidgetDetailEvent::class)]
    public function onWidgetDetailPreLoad(WidgetDetailEvent $event): void
    {
        $event->setTemplate('@MauticCore/Helper/chart.html.twig');
        $event->stopPropagation();
    }

    /**
     * Adds widget new widget types to the list of available widget types.
     */
    #[AsEventListener(event: WidgetTypeListEvent::class)]
    public function onWidgetListGenerate(WidgetTypeListEvent $event): void
    {
        if ($this->permissions && !$event->hasPermissions($this->permissions)) {
            return;
        }

        $widgetTypes = array_keys($this->types);

        foreach ($widgetTypes as $type) {
            $event->addType($type, $this->bundle);
        }
    }

    /**
     * Set a widget edit form when needed.
     */
    #[AsEventListener(event: WidgetFormEvent::class)]
    public function onWidgetFormGenerate(WidgetFormEvent $event): void
    {
        if (isset($this->types[$event->getType()])) {
            $event->setForm($this->types[$event->getType()]);
            $event->stopPropagation();
        }
    }

    /**
     * Set a widget detail when needed.
     */
    #[AsEventListener(event: GenerateWidgetDetailEvent::class)]
    public function onWidgetDetailGenerate(WidgetDetailEvent $event): void
    {
    }

    /**
     * Set a widget detail when needed.
     */
    public function checkPermissions(WidgetDetailEvent $event): void
    {
        $widgetTypes = array_keys($this->types);
        if ($this->permissions && !$event->hasPermissions($this->permissions) && in_array($event->getType(), $widgetTypes)) {
            $translator = $event->getTranslator();
            $event->setErrorMessage($translator->trans('mautic.dashboard.missing.permission', ['%section%' => $this->bundle]));
            $event->stopPropagation();

            return;
        }
    }
}
