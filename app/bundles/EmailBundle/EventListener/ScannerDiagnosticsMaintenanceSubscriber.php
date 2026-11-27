<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\EventListener;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\EmailBundle\Entity\ScannerDiagnostic;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


final readonly class ScannerDiagnosticsMaintenanceSubscriber implements EventSubscriberInterface
{
    private const BATCH_SIZE = 10000;

    public function __construct(
        private Connection $db,
        private TranslatorInterface $translator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::MAINTENANCE_CLEANUP_DATA => ['onDataCleanup', 0],
        ];
    }

    public function onDataCleanup(MaintenanceEvent $event): void
    {
        $table      = MAUTIC_TABLE_PREFIX.ScannerDiagnostic::TABLE_NAME;
        $parameters = ['date' => $event->getDate()->format('Y-m-d H:i:s')];

        if ($event->isDryRun()) {
            $sql  = "SELECT COUNT(*) FROM {$table} WHERE date_added <= :date";
            $rows = (int) $this->db->executeQuery($sql, $parameters)->fetchOne();
        } else {
            // Delete in batches, so a large table does not hold one long lock.
            $sql  = "DELETE FROM {$table} WHERE date_added <= :date LIMIT ".self::BATCH_SIZE;
            $rows = 0;

            do {
                $deleted = (int) $this->db->executeStatement($sql, $parameters);
                $rows += $deleted;
            } while ($deleted >= self::BATCH_SIZE);
        }

        $event->setStat($this->translator->trans('mautic.maintenance.email_scanner_diagnostics'), $rows, $sql, $parameters);
    }
}
