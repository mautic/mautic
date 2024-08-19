<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\ReportBundle\Event\AbstractReportEvent;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ReportSubscriber implements EventSubscriberInterface
{
    public const CONTEXT_AUDIT_LOG = 'audit.log';

    public function __construct(private CorePermissions $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
            ReportEvents::REPORT_ON_DISPLAY  => ['onReportDisplay', 0],
        ];
    }

    public function onReportBuilder(ReportBuilderEvent $event): void
    {
        if (!$this->allowAuditLogReportBuild($event)) {
            return;
        }

        $prefix  = 'al.';
        $columns = [
            $prefix.'user_id'    => [
                'alias' => 'user_id',
                'label' => 'mautic.audit_log.report.user_id',
                'type'  => 'int',
            ],
            $prefix.'user_name'  => [
                'alias' => 'user_name',
                'label' => 'mautic.audit_log.report.user_name',
                'type'  => 'string',
            ],
            $prefix.'object'     => [
                'alias' => 'object',
                'label' => 'mautic.audit_log.report.object',
                'type'  => 'select',
                'list'  => [
                    'email'                  => 'email',
                    'lead'                   => 'lead',
                    'company'                => 'company',
                    'client'                 => 'client',
                    'asset'                  => 'asset',
                    'campaign'               => 'campaign',
                    'category'               => 'category',
                    'message'                => 'message',
                    'dynamicContent'         => 'dynamicContent',
                    'form'                   => 'form',
                    'ContactExportScheduler' => 'ContactExportScheduler',
                    'ContactExports'         => 'ContactExports',
                    'import'                 => 'import',
                    'field'                  => 'field',
                    'note'                   => 'note',
                    'segment'                => 'segment',
                    'notification'           => 'notification',
                    'page'                   => 'page',
                    'point'                  => 'point',
                    'trigger'                => 'trigger',
                    'report'                 => 'report',
                    'sms'                    => 'sms',
                    'stage'                  => 'stage',
                    'user'                   => 'user',
                    'security'               => 'security',
                    'role'                   => 'role',
                    'webhook'                => 'webhook',
                    // 'config', intentionally ignored as it can contain passwords to various services
                ],
            ],
            $prefix.'object_id'  => [
                'alias' => 'object_id',
                'label' => 'mautic.audit_log.report.object_id',
                'type'  => 'int',
            ],
            $prefix.'action'     => [
                'alias' => 'action',
                'label' => 'mautic.audit_log.report.action',
                'type'  => 'select',
                'list'  => [
                    'create'     => 'create',
                    'update'     => 'update',
                    'delete'     => 'delete',
                    'sendEmail'  => 'sendEmail',
                    'identified' => 'identified',
                    'ipadded'    => 'ipadded',
                    'merge'      => 'merge',
                    'login'      => 'login',
                ],
            ],
            $prefix.'details'    => [
                'alias'    => 'details',
                'label'    => 'mautic.audit_log.report.details',
                'type'     => 'string',
                'collapse' => true,
            ],
            $prefix.'date_added' => [
                'alias' => 'date_added',
                'label' => 'mautic.audit_log.report.date_added',
                'type'  => 'datetime',
            ],
            $prefix.'ip_address' => [
                'alias' => 'ip_address',
                'label' => 'mautic.audit_log.report.ip_address',
                'type'  => 'string',
            ],
        ];

        $event->addTable(
            self::CONTEXT_AUDIT_LOG,
            [
                'display_name' => 'mautic.core.audit_log',
                'columns'      => $columns,
            ]
        );
    }

    public function onReportGenerate(ReportGeneratorEvent $event): void
    {
        if (!$this->allowAuditLogReportBuild($event)) {
            return;
        }

        $queryBuilder = $event->getQueryBuilder();

        $options  = $event->getOptions();
        $dateFrom = $options['dateFrom'];
        $dateTo   = $options['dateTo'];
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'audit_log', 'al');

        if ($dateFrom instanceof \DateTimeInterface) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte('al.date_added', ':dateFrom'));
            $queryBuilder->setParameter('dateFrom', $dateFrom->format(DateTimeHelper::FORMAT_DB));
        }

        if ($dateTo instanceof \DateTimeInterface) {
            $queryBuilder->andWhere($queryBuilder->expr()->lte('al.date_added', ':dateTo'));
            $queryBuilder->setParameter('dateTo', $dateTo->format(DateTimeHelper::FORMAT_DB));
        }

        $event->setQueryBuilder($queryBuilder);
    }

    public function onReportDisplay(ReportDataEvent $event): void
    {
        if (!$this->security->isAdmin() || !$event->checkContext([self::CONTEXT_AUDIT_LOG])) {
            return;
        }

        $data = $event->getData();

        foreach ($data as $key => $auditLog) {
            if (empty($auditLog['details'])) {
                continue;
            }

            $data[$key]['details'] = json_encode(unserialize($auditLog['details'], ['allowed_classes' => false]));
        }

        $event->setData($data);
    }

    private function allowAuditLogReportBuild(AbstractReportEvent $event): bool
    {
        // Allow to create audit_log report only for admin user and cli
        return ($this->security->isAdmin() || 'cli' === PHP_SAPI) && $event->checkContext([self::CONTEXT_AUDIT_LOG]);
    }
}
