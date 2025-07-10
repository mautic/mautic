<?php

namespace Mautic\ReportBundle;

/**
 * Events available for ReportBundle.
 */
final class ReportEvents
{
    /**
     * The mautic.report_pre_save event is dispatched right before a report is persisted.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    public const REPORT_PRE_SAVE = 'mautic.report_pre_save';

    /**
     * The mautic.report_post_save event is dispatched right after a report is persisted.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    public const REPORT_POST_SAVE = 'mautic.report_post_save';

    /**
     * The mautic.report_pre_delete event is dispatched prior to when a report is deleted.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    public const REPORT_PRE_DELETE = 'mautic.report_pre_delete';

    /**
     * The mautic.report_post_delete event is dispatched after a report is deleted.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportEvent instance.
     *
     * @var string
     */
    public const REPORT_POST_DELETE = 'mautic.report_post_delete';

    /**
     * The mautic.report_on_build event is dispatched before displaying the report builder form to allow
     * bundles to specify report sources and columns.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportBuilderEvent instance.
     *
     * @var string
     */
    public const REPORT_ON_BUILD = 'mautic.report_on_build';

    /**
     * The mautic.report_on_generate event is dispatched when generating a report to build the base query.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportGeneratorEvent instance.
     *
     * @var string
     */
    public const REPORT_ON_GENERATE = 'mautic.report_on_generate';

    /**
     * The mautic.report_query_pre_execute event is dispatched to allow a plugin to alter the query before execution.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportQueryEvent instance.
     *
     * @var string
     */
    public const REPORT_QUERY_PRE_EXECUTE = 'mautic.report_query_pre_execute';

    /**
     * The mautic.report_on_display event is dispatched when displaying a report.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportDataEvent instance.
     *
     * @var string
     */
    public const REPORT_ON_DISPLAY = 'mautic.report_on_display';

    /**
     * The mautic.report_on_graph_generate event is dispatched to generate a graph data.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportGraphEvent instance.
     *
     * @var string
     */
    public const REPORT_ON_GRAPH_GENERATE = 'mautic.report_on_graph_generate';

    /**
     * The mautic.report_schedule_send event is dispatched to send an exported report to a user.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ReportScheduleSendEvent instance.
     *
     * @var string
     */
    public const REPORT_SCHEDULE_SEND = 'mautic.report_schedule_send';

    /**
     * The mautic.report_on_column_collect event is dispatched during the report building to allow
     * bundles to add the columns of mapped objects.
     *
     * The event listener receives a Mautic\ReportBundle\Event\ColumnCollectEvent instance.
     *
     * @var string
     */
    public const REPORT_ON_COLUMN_COLLECT = 'mautic.report_on_column_collect';

    /**
     * The mautic.report_cleanup event is dispatched to cleanup report files after they had been sent via email.
     *
     * The event listener receives a Mautic\ReportBundle\Event\PermanentReportFileCreated instance.
     *
     * @var string
     */
    public const REPORT_PERMANENT_FILE_CREATED = 'mautic.report_permanent_file_created';
}
