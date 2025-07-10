<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder;

use Mautic\IntegrationsBundle\Event\InternalCompanyEvent;
use Mautic\IntegrationsBundle\Event\InternalContactEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindByIdEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\DAO\DateRange;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectProvider;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FullObjectReportBuilder
{
    public function __construct(
        private FieldBuilder $fieldBuilder,
        private ObjectProvider $objectProvider,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function buildReport(RequestDAO $requestDAO): ReportDAO
    {
        $syncReport       = new ReportDAO($requestDAO->getSyncToIntegration());
        $requestedObjects = $requestDAO->getObjects();
        $limit            = 200;
        $start            = $limit * ($requestDAO->getSyncIteration() - 1);

        foreach ($requestedObjects as $requestedObjectDAO) {
            try {
                DebugLogger::log(
                    $requestDAO->getSyncToIntegration(),
                    sprintf(
                        'Searching for %s objects between %s and %s (%d,%d)',
                        $requestedObjectDAO->getObject(),
                        $requestedObjectDAO->getFromDateTime()->format(DATE_ATOM),
                        $requestedObjectDAO->getToDateTime()->format(DATE_ATOM),
                        $start,
                        $limit
                    ),
                    self::class.':'.__FUNCTION__
                );

                $event = new InternalObjectFindEvent(
                    $this->objectProvider->getObjectByName($requestedObjectDAO->getObject())
                );

                if ($requestDAO->getInputOptionsDAO()->getMauticObjectIds()) {
                    $idChunks = array_chunk($requestDAO->getInputOptionsDAO()->getMauticObjectIds()->getObjectIdsFor($requestedObjectDAO->getObject()), $limit);
                    $idChunk  = $idChunks[$requestDAO->getSyncIteration() - 1] ?? [];
                    $event->setIds($idChunk);
                } else {
                    $event->setDateRange(
                        new DateRange(
                            $requestedObjectDAO->getFromDateTime(),
                            $requestedObjectDAO->getToDateTime()
                        )
                    );
                    $event->setStart($start);
                    $event->setLimit($limit);
                }

                $this->dispatcher->dispatch(
                    $event,
                    IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS
                );

                $foundObjects = $event->getFoundObjects();

                $this->processObjects($requestedObjectDAO, $syncReport, $foundObjects);
            } catch (ObjectNotFoundException $exception) {
                DebugLogger::log(
                    MauticSyncDataExchange::NAME,
                    $exception->getMessage(),
                    self::class.':'.__FUNCTION__
                );
            }
        }

        return $syncReport;
    }

    /**
     * @throws ObjectNotFoundException
     */
    private function processObjects(ObjectDAO $requestedObjectDAO, ReportDAO $syncReport, array $foundObjects): void
    {
        $fields = $requestedObjectDAO->getFields();

        if ($this->dispatcher->hasListeners(IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD)) {
            $event = new InternalObjectFindByIdEvent($this->objectProvider->getObjectByName($requestedObjectDAO->getObject()));
        }

        foreach ($foundObjects as $object) {
            $modifiedDateTime = new \DateTime(
                !empty($object['date_modified']) ? $object['date_modified'] : $object['date_added'],
                new \DateTimeZone('UTC')
            );
            $reportObjectDAO = new ReportObjectDAO($requestedObjectDAO->getObject(), $object['id'], $modifiedDateTime);
            $syncReport->addObject($reportObjectDAO);

            if (isset($event)) {
                // Update object id rather than creating the event again.
                $event->setId((int) $object['id']);
                $this->dispatcher->dispatch($event, IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD);

                if (!$event->getEntity()) {
                    // Object not found, continue.
                    continue;
                }

                try {
                    $this->dispatchBeforeFieldChangesEvent($syncReport->getIntegration(), $event->getEntity());
                } catch (InvalidValueException) {
                    // Object is not eligible, continue.
                    continue;
                }
            }

            foreach ($fields as $field) {
                try {
                    $reportFieldDAO = $this->fieldBuilder->buildObjectField($field, $object, $requestedObjectDAO, $syncReport->getIntegration());
                    $reportObjectDAO->addField($reportFieldDAO);
                } catch (FieldNotFoundException $exception) {
                    // Field is not supported so keep going
                    DebugLogger::log(
                        MauticSyncDataExchange::NAME,
                        $exception->getMessage(),
                        self::class.':'.__FUNCTION__
                    );
                }
            }
        }
    }

    /**
     * @throws InvalidValueException
     */
    private function dispatchBeforeFieldChangesEvent(string $integrationName, object $object): void
    {
        if ($object instanceof Lead) {
            if ($this->dispatcher->hasListeners(IntegrationEvents::INTEGRATION_BEFORE_FULL_CONTACT_REPORT_BUILD)) {
                $this->dispatcher->dispatch(
                    new InternalContactEvent($integrationName, $object),
                    IntegrationEvents::INTEGRATION_BEFORE_FULL_CONTACT_REPORT_BUILD
                );
            }

            return;
        }

        if ($object instanceof Company) {
            if ($this->dispatcher->hasListeners(IntegrationEvents::INTEGRATION_BEFORE_FULL_COMPANY_REPORT_BUILD)) {
                $this->dispatcher->dispatch(
                    new InternalCompanyEvent($integrationName, $object),
                    IntegrationEvents::INTEGRATION_BEFORE_FULL_COMPANY_REPORT_BUILD
                );
            }

            return;
        }

        throw new InvalidValueException('An object type should be specified. None matches.');
    }
}
