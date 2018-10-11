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
use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
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
use MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

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
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * @var array
     */
    private $fieldList = [];

    /**
     * @var array
     */
    private $lastProcessedTrackedId = [];

    /**
     * @var Router
     */
    private $router;

    /**
     * MauticSyncDataExchange constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     * @param MappingHelper                    $mappingHelper
     * @param CompanyObject                    $companyObjectHelper
     * @param ContactObject                    $contactObjectHelper
     * @param FieldModel                       $fieldModel
     * @param Router                           $router
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepository,
        VariableExpresserHelperInterface $variableExpresserHelper,
        MappingHelper $mappingHelper,
        CompanyObject $companyObjectHelper,
        ContactObject $contactObjectHelper,
        FieldModel $fieldModel,
        Router $router
    ) {
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->variableExpresserHelper = $variableExpresserHelper;
        $this->mappingHelper           = $mappingHelper;
        $this->companyObjectHelper     = $companyObjectHelper;
        $this->contactObjectHelper     = $contactObjectHelper;
        $this->fieldModel              = $fieldModel;
        $this->router                  = $router;
        $this->valueNormalizer         = new ValueNormalizer();
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
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
            try {
                $updateCount = count($updateObjects);
                DebugLogger::log(
                    self::NAME,
                    sprintf(
                        "Updating %d %s object(s)",
                        $updateCount,
                        $objectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                if (0 === $updateCount) {
                    continue;
                }

                $identifiedObjectIds = $syncOrderDAO->getIdentifiedObjectIds($objectName);

                switch ($objectName) {
                    case self::OBJECT_CONTACT:
                        $updatedObjectMappings = $this->contactObjectHelper->update($identifiedObjectIds, $updateObjects);
                        break;
                    case self::OBJECT_COMPANY:
                        $updatedObjectMappings = $this->companyObjectHelper->update($identifiedObjectIds, $updateObjects);
                        break;
                    default:
                        throw new ObjectNotSupportedException(self::NAME, $objectName);
                }

                $this->mappingHelper->updateObjectMappings($updatedObjectMappings);
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    self::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }

        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            try {
                $createCount = count($createObjects);

                DebugLogger::log(
                    self::NAME,
                    sprintf(
                        "Creating %d %s object(s)",
                        $createCount,
                        $objectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                if (0 === $createCount) {
                    continue;
                }

                switch ($objectName) {
                    case self::OBJECT_CONTACT:
                        $objectMappings = $this->contactObjectHelper->create($createObjects);
                        break;
                    case self::OBJECT_COMPANY:
                        $objectMappings = $this->companyObjectHelper->create($createObjects);
                        break;
                    default:
                        throw new ObjectNotSupportedException(self::NAME, $objectName);
                }

                $this->mappingHelper->saveObjectMappings($objectMappings);
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    self::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
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

        $fieldChanges = $this->fieldChangeRepository->findChangesForObject(
            $mappingManualDAO->getIntegration(),
            $internalObjectName,
            $internalObjectDAO->getObjectId()
        );
        foreach ($fieldChanges as $fieldChange) {
            $internalObjectDAO->addField(
                $this->getFieldChangeObject($fieldChange)
            );
        }

        return $internalObjectDAO;
    }

    /**
     * @param ObjectChangeDAO[] $objectChanges
     */
    public function cleanupProcessedObjects(array $objectChanges)
    {
        foreach ($objectChanges as $changedObjectDAO) {
            try {
                $object   = $this->getFieldObjectName($changedObjectDAO->getObject());
                $objectId = $changedObjectDAO->getObjectId();

                $this->fieldChangeRepository->deleteEntitiesForObject($objectId, $object, $changedObjectDAO->getIntegration());
            } catch (ObjectNotSupportedException $exception) {
                // Process the others
            }
        }
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    private function buildReportFromFullObjects(RequestDAO $requestDAO)
    {
        $syncReport       = new ReportDAO(self::NAME);
        $requestedObjects = $requestDAO->getObjects();

        $limit = 200;
        $start = $limit * ($requestDAO->getSyncIteration() - 1);

        foreach ($requestedObjects as $requestedObjectDAO) {
            try {
                DebugLogger::log(
                    self::NAME,
                    sprintf(
                        "Searching for %s objects between %s and %s (%d,%d)",
                        $requestedObjectDAO->getObject(),
                        $requestedObjectDAO->getFromDateTime()->format('Y:m:d H:i:s'),
                        $requestedObjectDAO->getToDateTime()->format('Y:m:d H:i:s'),
                        $start,
                        $limit
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                switch ($requestedObjectDAO->getObject()) {
                    case self::OBJECT_CONTACT:
                        $foundObjects = $this->contactObjectHelper->findObjectsBetweenDates(
                            $requestedObjectDAO->getFromDateTime(),
                            $requestedObjectDAO->getToDateTime(),
                            $start,
                            $limit
                        );
                        break;
                    case self::OBJECT_COMPANY:
                        $foundObjects = $this->companyObjectHelper->findObjectsBetweenDates(
                            $requestedObjectDAO->getFromDateTime(),
                            $requestedObjectDAO->getToDateTime(),
                            $start,
                            $limit
                        );
                        break;
                    default:
                        throw new ObjectNotSupportedException(self::NAME, $requestedObjectDAO->getObject());
                }

                $fields = $requestedObjectDAO->getFields();
                foreach ($foundObjects as $object) {
                    $modifiedDateTime = new \DateTime(
                        !empty($object['date_modified']) ? $object['date_modified'] : $object['date_added'],
                        new \DateTimeZone('UTC')
                    );
                    $reportObjectDAO  = new ReportObjectDAO($requestedObjectDAO->getObject(), $object['id'], $modifiedDateTime);
                    $syncReport->addObject($reportObjectDAO);

                    foreach ($fields as $field) {
                        $this->generateObjectField($field, $object, $requestedObjectDAO, $reportObjectDAO, $syncReport->getIntegration());
                    }
                }
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    self::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }

        return $syncReport;
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    private function buildReportFromTrackedChanges(RequestDAO $requestDAO)
    {
        $syncReport       = new ReportDAO(self::NAME);
        $requestedObjects = $requestDAO->getObjects();

        foreach ($requestedObjects as $objectDAO) {
            try {
                if (!isset($this->lastProcessedTrackedId[$objectDAO->getObject()])) {
                    $this->lastProcessedTrackedId[$objectDAO->getObject()] = 0;
                }

                $fieldsChanges = $this->fieldChangeRepository->findChangesBefore(
                    $requestDAO->getSyncToIntegration(),
                    $this->getFieldObjectName($objectDAO->getObject()),
                    $objectDAO->getToDateTime(),
                    $this->lastProcessedTrackedId[$objectDAO->getObject()]
                );

                $reportObjects = [];
                foreach ($fieldsChanges as $fieldChange) {
                    $objectId = (int) $fieldChange['object_id'];

                    // Track the last processed ID to prevent loops for objects that were set to be retried later
                    if ($objectId > $this->lastProcessedTrackedId[$objectDAO->getObject()]) {
                        $this->lastProcessedTrackedId[$objectDAO->getObject()] = $objectId;
                    }

                    $object           = $this->getObjectNameFromEntityName($fieldChange['object_type']);
                    $objectId         = (int) $fieldChange['object_id'];
                    $modifiedDateTime = new \DateTime($fieldChange['modified_at'], new \DateTimeZone('UTC'));

                    if (!array_key_exists($object, $reportObjects)) {
                        $reportObjects[$object] = [];
                    }

                    if (!array_key_exists($objectId, $reportObjects[$object])) {
                        /** @var ReportObjectDAO $reportObjectDAO */
                        $reportObjects[$object][$objectId] = $reportObjectDAO = new ReportObjectDAO($object, $objectId);
                        $syncReport->addObject($reportObjects[$object][$objectId]);
                        $reportObjectDAO->setChangeDateTime($modifiedDateTime);
                    }

                    /** @var ReportObjectDAO $reportObjectDAO */
                    $reportObjectDAO = $reportObjects[$object][$objectId];

                    $reportObjectDAO->addField(
                        $this->getFieldChangeObject($fieldChange)
                    );

                    // Track the latest change as the object's change date/time
                    if ($reportObjectDAO->getChangeDateTime() > $modifiedDateTime) {
                        $reportObjectDAO->setChangeDateTime($modifiedDateTime);
                    }
                }

                try {
                    $this->fillInMissingFields($syncReport, $objectDAO);
                } catch (ObjectNotFoundException $exception) {
                    // Process the others
                }
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    self::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
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

    /**
     * @param string $entityName
     *
     * @return string
     * @throws ObjectNotSupportedException
     */
    private function getObjectNameFromEntityName(string $entityName)
    {
        switch ($entityName) {
            case Lead::class:
                return self::OBJECT_CONTACT;
            case Company::class:
                return self::OBJECT_COMPANY;
            default:
                throw new ObjectNotSupportedException(self::NAME, $entityName);
        }
    }

    /**
     * @param string $object
     *
     * @return array
     */
    private function getFieldList(string $object)
    {
        if (!isset($this->fieldList[$object])) {
            $this->fieldList[$object] = $this->fieldModel->getFieldListWithProperties($object);
        }

        return $this->fieldList[$object];
    }

    /**
     * @param string $type
     *
     * @return string
     */
    private function getNormalizedFieldType(string $type)
    {
        switch ($type) {
            case 'boolean':
                return NormalizedValueDAO::BOOLEAN_TYPE;
            case 'date':
            case 'datetime':
            case 'time':
                return NormalizedValueDAO::DATETIME_TYPE;
            case 'number':
                return NormalizedValueDAO::FLOAT_TYPE;
            default:
                return NormalizedValueDAO::STRING_TYPE;
        }
    }

    /**
     * @param ReportDAO        $syncReport
     * @param RequestObjectDAO $requestObjectDAO
     *
     * @throws ObjectNotFoundException
     */
    private function fillInMissingFields(ReportDAO $syncReport, RequestObjectDAO $requestObjectDAO)
    {
        $objectName               = $requestObjectDAO->getObject();
        $fields                   = $requestObjectDAO->getFields();
        $objectsWithMissingFields = [];
        $syncObjects              = $syncReport->getObjects($objectName);

        foreach ($syncObjects as $syncObject) {
            $missingFields = [];
            foreach ($fields as $field) {
                try {
                    $syncObject->getField($field);
                } catch (FieldNotFoundException $exception) {
                    $missingFields[] = $field;
                }
            }

            if ($missingFields) {
                $objectsWithMissingFields[$syncObject->getObjectId()] = $missingFields;
            }
        }

        switch ($objectName) {
            case self::OBJECT_CONTACT:
                $mauticObjects = $this->contactObjectHelper->findObjectsByIds(array_keys($objectsWithMissingFields));
                break;
            case self::OBJECT_COMPANY:
                $mauticObjects = $this->companyObjectHelper->findObjectsByIds(array_keys($objectsWithMissingFields));
                break;
            default:
                throw new ObjectNotFoundException($objectName);
        }

        if (count($mauticObjects)) {
            foreach ($mauticObjects as $mauticObject) {
                $missingFields   = $objectsWithMissingFields[$mauticObject['id']];
                $reportObjectDAO = $syncReport->getObject($objectName, $mauticObject['id']);

                foreach ($missingFields as $field) {
                    $this->generateObjectField($field, $mauticObject, $requestObjectDAO, $reportObjectDAO, $syncReport->getIntegration());
                }
            }
        }
    }

    /**
     * @param string           $field
     * @param array            $mauticObject
     * @param RequestObjectDAO $requestObjectDAO
     * @param ReportObjectDAO  $reportObjectDAO
     * @param string           $integration
     */
    private function generateObjectField(
        string $field,
        array $mauticObject,
        RequestObjectDAO $requestObjectDAO,
        ReportObjectDAO $reportObjectDAO,
        string $integration
    ) {
        // Special handling of the ID field
        if ('mautic_internal_id' === $field) {
            $normalizedValue = new NormalizedValueDAO(
                NormalizedValueDAO::INT_TYPE,
                $mauticObject[$field]
            );

            $reportObjectDAO->addField(new ReportFieldDAO($field, $normalizedValue));

            return;
        }

        // Special handling of DNC fields
        if (strpos($field, 'mautic_internal_dnc_') === 0) {
            $channel = str_replace('mautic_internal_dnc_', '', $field);

            $normalizedValue = new NormalizedValueDAO(
                NormalizedValueDAO::INT_TYPE,
                $this->contactObjectHelper->getDoNotContactStatus((int) $mauticObject['id'], $channel)
            );

            $reportObjectDAO->addField(new ReportFieldDAO($field, $normalizedValue));

            return;
        }

        // Special handling of timeline URL
        if ('mautic_internal_contact_timeline' === $field) {
            $normalizedValue = new NormalizedValueDAO(
                NormalizedValueDAO::URL_TYPE,
                $this->router->generate(
                    'mautic_plugin_timeline_view',
                    [
                        'integration' => $integration,
                        'leadId'      => $mauticObject['id']
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );

            $reportObjectDAO->addField(new ReportFieldDAO($field, $normalizedValue));

            return;
        }

        // The rest should be Mautic custom fields and if not, just ignore
        $mauticFields = $this->getFieldList($requestObjectDAO->getObject());
        if (!isset($mauticFields[$field])) {
            // Field must have been deleted or something so let's skip
            return;
        }

        $requiredFields  = $requestObjectDAO->getRequiredFields();
        $fieldType       = $this->getNormalizedFieldType($mauticFields[$field]['type']);
        $normalizedValue = $this->valueNormalizer->normalizeForMautic($fieldType, $mauticObject[$field]);

        $reportObjectDAO->addField(
            new ReportFieldDAO(
                $field,
                $normalizedValue,
                (in_array($field, $requiredFields)) ? ReportFieldDAO::FIELD_REQUIRED : ReportFieldDAO::FIELD_UNCHANGED
            )
        );
    }
}
