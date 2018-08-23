<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\IntegrationsBundle\Sync\Mapping\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO AS ReportFieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject\CompanyObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\InternalObject\ContactObject;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use MauticPlugin\MagentoBundle\Exception\ObjectNotSupportedException;

/**
 * Class MauticSyncDataExchange
 */
class MauticSyncDataExchange implements SyncDataExchangeInterface
{
    const NAME = 'mautic';
    const OBJECT_CONTACT = 'lead'; // kept as lead for BC
    const OBJECT_COMPANY = 'company';

    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepository;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpresserHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var CompanyObject
     */
    private $companyObjectHelper;

    /**
     * @var ContactObject
     */
    private $contactObjectHelper;

    /**
     * MauticSyncDataExchange constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     * @param MappingHelper                    $mappingHelper
     * @param CompanyObject                    $companyObjectHelper
     * @param ContactObject                    $contactObjectHelper
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepository,
        VariableExpresserHelperInterface $variableExpresserHelper,
        MappingHelper $mappingHelper,
        CompanyObject $companyObjectHelper,
        ContactObject $contactObjectHelper
    ) {
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->variableExpresserHelper = $variableExpresserHelper;
        $this->mappingHelper           = $mappingHelper;
        $this->companyObjectHelper     = $companyObjectHelper;
        $this->contactObjectHelper     = $contactObjectHelper;
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     * @throws ObjectNotSupportedException
     */
    public function getSyncReport(RequestDAO $requestDAO)
    {
        if ($requestDAO->isFirstTimeSync()) {
            return $this->buildReportFromFullObjects($requestDAO);
        }

        return $this->buildReportFromTrackedChanges($requestDAO);
    }

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO)
    {
        $identifiedObjects = $syncOrderDAO->getIdentifiedObjects();
        foreach ($identifiedObjects as $objectName => $updateObjects) {
            $identifiedObjectIds = $syncOrderDAO->getIdentifiedObjectIds($objectName);

            switch ($objectName) {
                case self::OBJECT_CONTACT:
                    $this->contactObjectHelper->update($identifiedObjectIds, $updateObjects);
                    break;
                case self::OBJECT_COMPANY:
                    $this->companyObjectHelper->update($identifiedObjectIds, $updateObjects);
                    break;
            }
        }

        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            switch ($objectName) {
                case self::OBJECT_CONTACT:
                    $this->contactObjectHelper->create($createObjects);
                    break;
                case self::OBJECT_COMPANY:
                    $this->companyObjectHelper->create($createObjects);
                    break;
            }
        }
    }

    /**
     * @param MappingManualDAO $mappingManualDAO
     * @param string           $internalObjectName
     * @param ReportObjectDAO  $integrationObjectDAO
     *
     * @return ReportObjectDAO
     */
    public function getConflictedInternalObject(MappingManualDAO $mappingManualDAO, string $internalObjectName, ReportObjectDAO $integrationObjectDAO)
    {
        // Check to see if we have a match
        $internalObjectDAO = $this->mappingHelper->findMauticObject($mappingManualDAO, $internalObjectName, $integrationObjectDAO);

        if (!$internalObjectDAO) {
            return new ReportObjectDAO($internalObjectName, null);
        }

        $fieldChanges = $this->fieldChangeRepository->findChangesForObject($internalObjectName, $internalObjectDAO->getObjectId());
        foreach ($fieldChanges as $fieldChange) {
            $internalObjectDAO->addField(
                $this->getFieldChangeObject($fieldChange)
            );
        }

        return $internalObjectDAO;
    }

    /**
     * @param string          $integration
     * @param string          $integrationObjectName
     * @param ReportObjectDAO $internalObjectDAO
     *
     * @return ReportObjectDAO
     */
    public function getMappedIntegrationObject(string $integration, string $integrationObjectName, ReportObjectDAO $internalObjectDAO)
    {
        $integrationObject = $this->mappingHelper->findIntegrationObject($integration, $integrationObjectName, $internalObjectDAO);

        if ($integrationObject) {
            return $integrationObject;
        }

        return new ReportObjectDAO($integrationObjectName, null);
    }

    /**
     * @param array $mappings
     */
    public function saveObjectMappings(array $mappings)
    {
        foreach ($mappings as $mapping) {
            $this->mappingHelper->saveObjectMapping($mapping);
        }
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     * @throws ObjectNotSupportedException
     */
    private function buildReportFromFullObjects(RequestDAO $requestDAO)
    {
        $syncReport       = new ReportDAO(self::NAME);
        $requestedObjects = $requestDAO->getObjects();

        $limit = 200;
        $start = $limit * ($requestDAO->getSyncIteration() - 1);

        foreach ($requestedObjects as $objectDAO) {
            switch ($objectDAO->getObject()) {
                case self::OBJECT_CONTACT:
                    $foundObjects = $this->contactObjectHelper->findObjectsBetweenDates($objectDAO->getFromDateTime(), $objectDAO->getToDateTime(), $start, $limit);
                    break;
                case self::OBJECT_COMPANY:
                    $foundObjects = $this->companyObjectHelper->findObjectsBetweenDates($objectDAO->getFromDateTime(), $objectDAO->getToDateTime(), $start, $limit);
                    break;
                default:
                    throw new ObjectNotSupportedException(self::NAME, $objectDAO->getObject());
            }

            $reportObjects = [];
            foreach ($foundObjects as $object) {
//                $objectDAO = new ReportObjectDAO($objectDAO->getObject(), $object['id']);
//                $syncReport->addObject($objectDAO);
//
//                $this->variableExpresserHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));
//
//                $reportFieldDAO = new ReportFieldDAO($fieldChange['column_name'], $newValue);
//                $objectDAO->addField(
//
//                );
            }
        }
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     * @throws ObjectNotSupportedException
     */
    private function buildReportFromTrackedChanges(RequestDAO $requestDAO)
    {
        $syncReport       = new ReportDAO(self::NAME);
        $requestedObjects = $requestDAO->getObjects();

        foreach ($requestedObjects as $objectDAO) {
            $fieldsChanges = $this->fieldChangeRepository->findChangesAfter(
                $this->getFieldObjectName($objectDAO->getObject()),
                $objectDAO->getFromDateTime()
            );

            $reportObjects = [];
            foreach ($fieldsChanges as $fieldChange) {
                $object   = $fieldChange['object'];
                $objectId = $fieldChange['object_id'];

                if (!array_key_exists($object, $reportObjects)) {
                    $reportObjects[$object] = [];
                }

                if (!array_key_exists($objectId, $reportObjects[$object])) {
                    $reportObjects[$object][$objectId] = new ReportObjectDAO($object, $objectId);
                    $syncReport->addObject($reportObjects[$object][$objectId]);
                }

                /** @var ReportObjectDAO $reportObjectDAO */
                $reportObjectDAO = $reportObjects[$object][$objectId];

                $reportObjectDAO->addField(
                    $this->getFieldChangeObject($fieldChange)
                );
            }
        }

        return $syncReport;
    }

    /**
     * @param array $fieldChange
     *
     * @return ReportFieldDAO
     */
    private function getFieldChangeObject(array $fieldChange)
    {
        $changeTimestamp = new \DateTimeImmutable($fieldChange['modified_at'], new \DateTimeZone('UTC'));
        $columnType      = $fieldChange['column_type'];
        $columnValue     = $fieldChange['column_value'];
        $newValue        = $this->variableExpresserHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));

        $reportFieldDAO = new ReportFieldDAO($fieldChange['column_name'], $newValue);
        $reportFieldDAO->setChangeDateTime($changeTimestamp);

        return $reportFieldDAO;
    }

    /**
     * @param string $objectName
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    private function getFieldObjectName(string $objectName)
    {
        switch ($objectName) {
            case self::OBJECT_CONTACT:
                return Lead::class;
            case self::OBJECT_COMPANY:
                return Company::class;
            default:
                throw new ObjectNotSupportedException(self::NAME, $objectName);
        }
    }
}
