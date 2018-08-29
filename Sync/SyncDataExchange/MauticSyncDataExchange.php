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
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
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
use MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
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
     * MauticSyncDataExchange constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     * @param MappingHelper                    $mappingHelper
     * @param CompanyObject                    $companyObjectHelper
     * @param ContactObject                    $contactObjectHelper
     * @param FieldModel                       $fieldModel
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepository,
        VariableExpresserHelperInterface $variableExpresserHelper,
        MappingHelper $mappingHelper,
        CompanyObject $companyObjectHelper,
        ContactObject $contactObjectHelper,
        FieldModel $fieldModel
    )
    {
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->variableExpresserHelper = $variableExpresserHelper;
        $this->mappingHelper           = $mappingHelper;
        $this->companyObjectHelper     = $companyObjectHelper;
        $this->contactObjectHelper     = $contactObjectHelper;
        $this->fieldModel              = $fieldModel;
        $this->valueNormalizer         = new ValueNormalizer();
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
     *
     * @throws ObjectNotSupportedException
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO)
    {
        $identifiedObjects = $syncOrderDAO->getIdentifiedObjects();
        foreach ($identifiedObjects as $objectName => $updateObjects) {
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
                    $this->contactObjectHelper->update($identifiedObjectIds, $updateObjects);
                    break;
                case self::OBJECT_COMPANY:
                    $this->companyObjectHelper->update($identifiedObjectIds, $updateObjects);
                    break;
                default:
                    throw new ObjectNotSupportedException(self::NAME, $objectName);
            }
        }

        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($unidentifiedObjects as $objectName => $createObjects) {
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

            $this->saveObjectMappings($objectMappings);
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
            $mauticFields = $this->getFieldList($objectDAO->getObject());

            DebugLogger::log(
                self::NAME,
                sprintf(
                    "Searching for %s objects between %s and %s (%d,%d)",
                    $objectDAO->getObject(),
                    $objectDAO->getFromDateTime()->format('Y:m:d H:i:s'),
                    $objectDAO->getToDateTime()->format('Y:m:d H:i:s'),
                    $start,
                    $limit
                ),
                __CLASS__.':'.__FUNCTION__
            );

            switch ($objectDAO->getObject()) {
                case self::OBJECT_CONTACT:
                    $foundObjects = $this->contactObjectHelper->findObjectsBetweenDates(
                        $objectDAO->getFromDateTime(),
                        $objectDAO->getToDateTime(),
                        $start,
                        $limit
                    );
                    break;
                case self::OBJECT_COMPANY:
                    $foundObjects = $this->companyObjectHelper->findObjectsBetweenDates(
                        $objectDAO->getFromDateTime(),
                        $objectDAO->getToDateTime(),
                        $start,
                        $limit
                    );
                    break;
                default:
                    throw new ObjectNotSupportedException(self::NAME, $objectDAO->getObject());
            }

            $fields = $objectDAO->getFields();
            foreach ($foundObjects as $object) {
                $modifiedDateTime = new \DateTime(
                    !empty($object['date_modified']) ? $object['date_modified'] : $object['date_added'],
                    new \DateTimeZone('UTC')
                );
                $objectDAO        = new ReportObjectDAO($objectDAO->getObject(), $object['id'], $modifiedDateTime);
                $syncReport->addObject($objectDAO);

                foreach ($fields as $field) {
                    $fieldType       = $this->getNormalizedFieldType($mauticFields[$field]['type']);
                    $normalizedValue = new NormalizedValueDAO(
                        $fieldType,
                        $object[$field]
                    );

                    $objectDAO->addField(new ReportFieldDAO($field, $normalizedValue));
                }
            }
        }

        return $syncReport;
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
                $object   = $fieldChange['object_type'];
                $objectId = $fieldChange['object_id'];
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
}
