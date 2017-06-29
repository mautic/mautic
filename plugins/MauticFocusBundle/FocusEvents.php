<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFocusBundle;

/**
 * Class FocusEvents.
 *
 * Events available for MauticFocusBundle
 */
final class FocusEvents
{
    /**
     * The mautic.focus_pre_save event is dispatched right before a focus is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const PRE_SAVE = 'mautic.focus_pre_save';

    /**
     * The mautic.focus_post_save event is dispatched right after a focus is persisted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const POST_SAVE = 'mautic.focus_post_save';

    /**
     * The mautic.focus_pre_delete event is dispatched before a focus is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const PRE_DELETE = 'mautic.focus_pre_delete';

    /**
     * The mautic.focus_post_delete event is dispatched after a focus is deleted.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const POST_DELETE = 'mautic.focus_post_delete';

    /**
     * The mautic.focus_token_replacent event is dispatched after a load content.
     *
     * The event listener receives a MauticPlugin\MauticFocusBundle\Event\FocusEvent instance.
     *
     * @var string
     */
    const TOKEN_REPLACEMENT = 'mautic.focus_token_replacement';
}
