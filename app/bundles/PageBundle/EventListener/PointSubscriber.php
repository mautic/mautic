<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\Form\Type\PointActionPageHitType;
use Mautic\PageBundle\Form\Type\PointActionUrlHitType;
use Mautic\PageBundle\Helper\PointActionHelper;
use Mautic\PageBundle\PageEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    /**
     * @var PointModel
     */
    private $pointModel;

    public function __construct(PointModel $pointModel)
    {
        $this->pointModel = $pointModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_ON_BUILD => ['onPointBuild', 0],
            PageEvents::PAGE_ON_HIT     => ['onPageHit', 0],
        ];
    }

    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.page.point.action',
            'label'       => 'mautic.page.point.action.pagehit',
            'description' => 'mautic.page.point.action.pagehit_descr',
            'callback'    => [PointActionHelper::class, 'validatePageHit'],
            'formType'    => PointActionPageHitType::class,
        ];

        $event->addAction('page.hit', $action);

        $action = [
            'group'       => 'mautic.page.point.action',
            'label'       => 'mautic.page.point.action.urlhit',
            'description' => 'mautic.page.point.action.urlhit_descr',
            'callback'    => [PointActionHelper::class, 'validateUrlHit'],
            'formType'    => PointActionUrlHitType::class,
            'formTheme'   => 'MauticPageBundle:FormTheme\Point',
        ];

        $event->addAction('url.hit', $action);
    }

    /**
     * Trigger point actions for page hits.
     */
    public function onPageHit(Events\PageHitEvent $event)
    {
        if ($event->getPage()) {
            // Mautic Landing Page was hit
            $this->pointModel->triggerAction('page.hit', $event->getHit());
        } else {
            // Mautic Tracking Pixel was hit
            $this->pointModel->triggerAction('url.hit', $event->getHit());
        }
    }
}
