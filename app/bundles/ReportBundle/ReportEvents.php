<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle;

/**
 * Class ReportEvents
 * Events available for ReportBundle
 *
 * @package Mautic\ReportBundle
 */
final class ReportEvents
{

    /**
     * The mautic.report_pre_save event is thrown right before a report is persisted.
     *
     * The event listener receives a
     * Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    const REPORT_PRE_SAVE = 'mautic.report_pre_save';

    /**
     * The mautic.report_post_save event is thrown right after a report is persisted.
     *
     * The event listener receives a
     * Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    const REPORT_POST_SAVE = 'mautic.report_post_save';

    /**
     * The mautic.report_pre_delete event is thrown prior to when a report is deleted.
     *
     * The event listener receives a
     * Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    const REPORT_PRE_DELETE = 'mautic.report_pre_delete';

    /**
     * The mautic.report_post_delete event is thrown after a report is deleted.
     *
     * The event listener receives a
     * Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    const REPORT_POST_DELETE = 'mautic.report_post_delete';

    /**
     * The mautic.report_on_build event is thrown before displaying the report builder form to allow
     * bundles to specify report sources and columns
     *
     * The event listener receives a
     * Mautic\ReportBundle\Event\ReportBuilderEvent instance.
     *
     * @var string
     */
    const REPORT_ON_BUILD = 'mautic.report_on_build';
}
