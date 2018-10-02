<?php
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://Mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticDNCEventBundle;

/**
 * Class LeadEvents
 * Events available for LeadBundle.
 */
final class MauticDNCEventEvents
{
    /**
     * The Mautic.lead_pre_save event is dispatched right before a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const DNCEVENT_ADD_DNC = 'Mautic.dncevent.event.addDnc';
    const DNCEVENT_REMOVE_DNC = 'Mautic.dncevent.event.removeDnc';

}
