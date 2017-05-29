<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle;

/**
 * Class MessengerEvents
 * Events available for MauticMessengerBundle.
 */
final class MessengerEvents
{
    /**
     * The mautic.messenger_on_send event is dispatched when an message is sent.
     *
     * The event listener receives a
     * MauticPlugin\MauticMessengerBundle\Event\MessengerSendEvent instance.
     *
     * @var string
     */
    const MESSENGER_ON_SEND = 'mautic.messenger_on_send';
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.messenger.on_campaign_trigger_action';

}
