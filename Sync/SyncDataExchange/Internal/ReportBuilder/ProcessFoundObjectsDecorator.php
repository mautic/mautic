<?php

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

trait ProcessFoundObjectsDecorator
{
    /**
     * @param ObjectDAO $requestedObjectDAO
     * @param ReportDAO $syncReport
     * @param array     $foundObjects
     */
    private function processObjects(ObjectDAO $requestedObjectDAO, ReportDAO $syncReport, array $foundObjects, FieldBuilder $fieldBuilder)
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
                    $reportFieldDAO = $fieldBuilder->buildObjectField($field, $object, $requestedObjectDAO, $syncReport->getIntegration());
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