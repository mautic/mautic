<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber.
 */
class PointSubscriber extends CommonSubscriber
{
    /**
     * @var PointModel
     */
    protected $pointModel;

    /**
     * PointSubscriber constructor.
     *
     * @param PointModel $pointModel
     */
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

    /**
     * @param PointBuilderEvent $event
     */
    public function onPointBuild(PointBuilderEvent $event)
    {
        $action = [
            'group'       => 'mautic.page.point.action',
            'label'       => 'mautic.page.point.action.pagehit',
            'description' => 'mautic.page.point.action.pagehit_descr',
            'callback'    => ['\\Mautic\\PageBundle\\Helper\\PointActionHelper', 'validatePageHit'],
            'formType'    => 'pointaction_pagehit',
        ];

        $event->addAction('page.hit', $action);

        $action = [
            'group'       => 'mautic.page.point.action',
            'label'       => 'mautic.page.point.action.urlhit',
            'description' => 'mautic.page.point.action.urlhit_descr',
            'callback'    => ['\\Mautic\\PageBundle\\Helper\\PointActionHelper', 'validateUrlHit'],
            'formType'    => 'pointaction_urlhit',
            'formTheme'   => 'MauticPageBundle:FormTheme\Point',
        ];

        $event->addAction('url.hit', $action);
    }

    /**
     * Trigger point actions for page hits.
     *
     * @param Events\PageHitEvent $event
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
