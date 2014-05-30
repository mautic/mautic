<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle;

/**
 * Class LeadEvents
 * Events available for LeadBundle
 *
 * @package Mautic\LeadBundle
 */
final class LeadEvents
{
    /**
     * The mautic.lead_pre_save event is thrown right before a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_PRE_SAVE   = 'mautic.lead_pre_save';

    /**
     * The mautic.lead_post_save event is thrown right after a lead is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_POST_SAVE   = 'mautic.lead_post_save';

    /**
     * The mautic.lead_score_change event is thrown if a lead's score changes.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\ScoreChangeEvent instance.
     *
     * @var string
     */
    const LEAD_SCORE_CHANGE   = 'mautic.lead_score_change';

    /**
     * The mautic.lead_pre_delete event is thrown before a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_PRE_DELETE   = 'mautic.lead_pre_delete';

    /**
     * The mautic.lead_post_delete event is thrown after a lead is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LEAD_POST_DELETE   = 'mautic.lead_post_delete';

    /**
     * The mautic.lead_list_pre_save event is thrown right before a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const LIST_PRE_SAVE   = 'mautic.lead_list_pre_save';

    /**
     * The mautic.lead_list_post_save event is thrown right after a lead_list is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_POST_SAVE   = 'mautic.lead_list_post_save';

    /**
     * The mautic.lead_list_pre_delete event is thrown before a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_PRE_DELETE   = 'mautic.lead_list_pre_delete';

    /**
     * The mautic.lead_list_post_delete event is thrown after a lead_list is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadListEvent instance.
     *
     * @var string
     */
    const LIST_POST_DELETE   = 'mautic.lead_list_post_delete';


    /**
     * The mautic.lead_field_pre_save event is thrown right before a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_SAVE   = 'mautic.lead_field_pre_save';

    /**
     * The mautic.lead_field_post_save event is thrown right after a lead_field is persisted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_SAVE   = 'mautic.lead_field_post_save';

    /**
     * The mautic.lead_field_pre_delete event is thrown before a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_PRE_DELETE   = 'mautic.lead_field_pre_delete';

    /**
     * The mautic.lead_field_post_delete event is thrown after a lead_field is deleted.
     *
     * The event listener receives a
     * Mautic\LeadBundle\Event\LeadFieldEvent instance.
     *
     * @var string
     */
    const FIELD_POST_DELETE   = 'mautic.lead_field_post_delete';
}