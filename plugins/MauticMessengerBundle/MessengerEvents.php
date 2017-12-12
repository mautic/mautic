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
     * The mautic.messenger_token_replacement event is thrown right before the content is returned.
     *
     * The event listener receives a
     * Mautic\CoreBundle\Event\TokenReplacementEvent instance.
     *
     * @var string
     */
    const TOKEN_REPLACEMENT = 'mautic.messenger_token_replacement';

    /**
     * The mautic.messenger_pre_save event is thrown right before a asset is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticMessengerBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const PRE_SAVE = 'mautic.messenger_pre_save';

    /**
     * The mautic.messenger_post_save event is thrown right after a asset is persisted.
     *
     * The event listener receives a
     * MauticPlugin\MauticMessengerBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const POST_SAVE = 'mautic.messenger_post_save';

    /**
     * The mautic.messenger_pre_delete event is thrown prior to when a asset is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticMessengerBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const PRE_DELETE = 'mautic.messenger_pre_delete';

    /**
     * The mautic.messenger_post_delete event is thrown after a asset is deleted.
     *
     * The event listener receives a
     * MauticPlugin\MauticMessengerBundle\Event\DynamicContentEvent instance.
     *
     * @var string
     */
    const POST_DELETE = 'mautic.messenger_post_delete';

    /**
     * The mautic.category_pre_save event is thrown right before a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_SAVE = 'mautic.category_pre_save';

    /**
     * The mautic.category_post_save event is thrown right after a category is persisted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_SAVE = 'mautic.category_post_save';

    /**
     * The mautic.category_pre_delete event is thrown prior to when a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_PRE_DELETE = 'mautic.category_pre_delete';

    /**
     * The mautic.category_post_delete event is thrown after a category is deleted.
     *
     * The event listener receives a
     * Mautic\CategoryBundle\Event\CategoryEvent instance.
     *
     * @var string
     */
    const CATEGORY_POST_DELETE = 'mautic.category_post_delete';

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
