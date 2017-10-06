<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticRecombeeBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PageBundle\Event as Events;
use Mautic\PageBundle\PageEvents;

/**
 * Class PageSubscriber.
 */
class PageSubscriber extends CommonSubscriber
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PageEvents::PAGE_ON_HIT => ['onPageHit', 0],
        ];
    }

    /**
     * Trigger point actions for page hits.
     *
     * @param Events\PageHitEvent $event
     */
    public function onPageHit(Events\PageHitEvent $event)
    {
        // import starych dat
        // import aktualnych dat - realtime - jednoducha integracia
        // page hits
        // product matchs
        // Kombinacia s DB ak existuje load
        // spatne doplnenie ak produkt je novy
        // identifikator produktu
        // produkt > vlastnosti v xml
        // visitor >
        //mtc_id
        $hit      = $event->getHit();
        $redirect = $hit->getRedirect();
        if ($redirect && $email = $hit->getEmail()) {
        }
    }
}
