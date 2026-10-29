<?php

namespace Mautic\DashboardBundle\EventListener;

use Mautic\DashboardBundle\Event\GenerateWidgetDetailEvent;
use Mautic\DashboardBundle\Event\PreLoadWidgetDetailEvent;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\DashboardBundle\Event\WidgetFormEvent;
use Mautic\DashboardBundle\Event\WidgetTypeListEvent;
<<<<<<< HEAD
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
=======
>>>>>>> 647a48f3a0 (fixup! rebase)

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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: PreLoadWidgetDetailEvent::class,
        priority: 0
    )]
    public function onWidgetDetailPreLoad(WidgetDetailEvent $event): void
    {
        $event->setTemplate('@MauticCore/Helper/chart.html.twig');
        $event->stopPropagation();
    }
    /**
     * Adds widget new widget types to the list of available widget types.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: WidgetTypeListEvent::class,
        priority: 0
    )]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: WidgetFormEvent::class,
        priority: 0
    )]
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
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: GenerateWidgetDetailEvent::class,
        priority: 0
    )]
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
