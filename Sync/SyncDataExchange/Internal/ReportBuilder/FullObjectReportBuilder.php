<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\CompanyObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ContactObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class FullObjectReportBuilder
{
    /**
     * @var ContactObject
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObject
     */
    private $companyObjectHelper;

    /**
     * @var FieldBuilder
     */
    private $fieldBuilder;

    /**
     * FullObjectReportBuilder constructor.
     *
     * @param ContactObject $contactObjectHelper
     * @param CompanyObject $companyObjectHelper
     * @param FieldBuilder  $fieldBuilder
     */
    public function __construct(ContactObject $contactObjectHelper, CompanyObject $companyObjectHelper, FieldBuilder $fieldBuilder)
    {
        $this->contactObjectHelper = $contactObjectHelper;
        $this->companyObjectHelper = $companyObjectHelper;
        $this->fieldBuilder        = $fieldBuilder;
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function buildReport(RequestDAO $requestDAO): ReportDAO
    {
        $syncReport       = new ReportDAO(MauticSyncDataExchange::NAME);
        $requestedObjects = $requestDAO->getObjects();

        $limit = 200;
        $start = $limit * ($requestDAO->getSyncIteration() - 1);

        foreach ($requestedObjects as $requestedObjectDAO) {
            try {
                DebugLogger::log(
                    MauticSyncDataExchange::NAME,
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
                    case MauticSyncDataExchange::OBJECT_CONTACT:
                        $foundObjects = $this->contactObjectHelper->findObjectsBetweenDates(
                            $requestedObjectDAO->getFromDateTime(),
                            $requestedObjectDAO->getToDateTime(),
                            $start,
                            $limit
                        );

                        break;
                    case MauticSyncDataExchange::OBJECT_COMPANY:
                        $foundObjects = $this->companyObjectHelper->findObjectsBetweenDates(
                            $requestedObjectDAO->getFromDateTime(),
                            $requestedObjectDAO->getToDateTime(),
                            $start,
                            $limit
                        );
                        break;
                    default:
                        throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $requestedObjectDAO->getObject());
                }

                $this->processObjects($requestedObjectDAO, $syncReport, $foundObjects);

            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    MauticSyncDataExchange::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }

        return $syncReport;
    }

    /**
     * @param ObjectDAO $requestedObjectDAO
     * @param ReportDAO $syncReport
     * @param array     $foundObjects
     */
    private function processObjects(ObjectDAO $requestedObjectDAO, ReportDAO $syncReport, array $foundObjects)
    {
        $fields = $requestedObjectDAO->getFields();
        foreach ($foundObjects as $object) {
            $modifiedDateTime = new \DateTime(
                !empty($object['date_modified']) ? $object['date_modified'] : $object['date_added'],
                new \DateTimeZone('UTC')
            );
            $reportObjectDAO  = new ReportObjectDAO($requestedObjectDAO->getObject(), $object['id'], $modifiedDateTime);
            $syncReport->addObject($reportObjectDAO);

            foreach ($fields as $field) {
                try {
                    $reportFieldDAO = $this->fieldBuilder->buildObjectField($field, $object, $requestedObjectDAO, $syncReport->getIntegration());
                    $reportObjectDAO->addField($reportFieldDAO);
                } catch (FieldNotFoundException $exception) {
                    // Field is not supported so keep going
                    DebugLogger::log(
                        MauticSyncDataExchange::NAME,
                        $exception->getMessage(),
                        __CLASS__.':'.__FUNCTION__
                    );
                }
            }
        }
    }
}