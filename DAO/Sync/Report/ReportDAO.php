<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report;

use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\InformationChangeRequestDAO;

/**
 * Class SyncReportDAO
 * @package Mautic\PluginBundle\Model\Sync\DAO
 */
class ReportDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    /**
     * SyncReportDAO constructor.
     * @param $integration
     */
    public function __construct($integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @param ObjectDAO $objectDAO
     * @return $this
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        $this->objects[$objectDAO->getObject()][$objectDAO->getObjectId()] = $objectDAO;

        return $this;
    }

    /**
     * @param string $objectName
     * @param int    $objectId
     * @param string $fieldName
     *
     * @return InformationChangeRequestDAO
     */
    public function getInformationChangeRequest($objectName, $objectId, $fieldName)
    {
        if (!isset($this->objects[$objectName][$objectId])) {
            return null;
        }
        /** @var ObjectDAO $reportObject */
        $reportObject = $this->objects[$objectName][$objectId];
        $reportField = $reportObject->getField($fieldName);
        $informationChangeRequest = new InformationChangeRequestDAO(
            $this->integration,
            $objectName,
            $objectId,
            $fieldName,
            $reportField->getValue()
        );
        $informationChangeRequest->setPossibleChangeTimestamp($reportObject->getChangeTimestamp())
            ->setCertainChangeTimestamp($reportField->getChangeTimestamp());
        return $informationChangeRequest;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects()
    {
        return $this->objects;
    }
}
