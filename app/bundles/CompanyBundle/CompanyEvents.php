<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CompanyBundle;

/**
 * Class CompanyEvents
 *
 * Events available for CompanyBundle
 */
final class CompanyEvents
{
    /**
     * The mautic.company_pre_save event is thrown right before a form is persisted.
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_PRE_SAVE = 'mautic.company_pre_save';

    /**
     * The mautic.company_post_save event is thrown right after a form is persisted.
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_POST_SAVE = 'mautic.company_post_save';

    /**
     * The mautic.company_pre_delete event is thrown before a form is deleted.
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_PRE_DELETE = 'mautic.company_pre_delete';

    /**
     * The mautic.company_post_delete event is thrown after a form is deleted.
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyEvent instance.
     *
     * @var string
     */
    const COMPANY_POST_DELETE = 'mautic.company_post_delete';

    /**
     * The mautic.company_on_build event is thrown before displaying the company builder form to allow adding of custom actions
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyBuilderEvent instance.
     *
     * @var string
     */
    const COMPANY_ON_BUILD = 'mautic.company_on_build';

    /**
     * The mautic.company_on_action event is thrown to execute a company action
     *
     * The event listener receives a Mautic\CompanyBundle\Event\CompanyActionEvent instance.
     *
     * @var string
     */
    const COMPANY_ON_ACTION = 'mautic.company_on_action';
    /**
     * The mautic.company.on_campaign_trigger_action event is fired when the campaign action triggers.
     *
     * The event listener receives a
     * Mautic\CampaignBundle\Event\CampaignExecutionEvent
     *
     * @var string
     */
    const ON_CAMPAIGN_TRIGGER_ACTION = 'mautic.company.on_campaign_trigger_action';
}
