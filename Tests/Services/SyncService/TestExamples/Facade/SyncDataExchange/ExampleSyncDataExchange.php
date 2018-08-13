<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\Facade\SyncDataExchange;

use MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\Integration\ExampleIntegration;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\EntityMappingDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Value\NormalizedValueDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\FieldDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\ValueNormalizer\ValueNormalizer;

class ExampleSyncDataExchange implements SyncDataExchangeInterface
{
    const LEAD_OBJECT = 'lead';
    const CONTACT_OBJECT = 'contact';

    /**
     * @var array
     */
    const FIELDS = [
        'id'            => [
            'label' => 'ID',
            'type'  => NormalizedValueDAO::INT_TYPE,
        ],
        'first_name'    => [
            'label' => 'First Name',
            'type'  => NormalizedValueDAO::STRING_TYPE,
        ],
        'last_name'     => [
            'label' => 'Last Name',
            'type'  => NormalizedValueDAO::STRING_TYPE,
        ],
        'email'         => [
            'label' => 'Email',
            'type'  => NormalizedValueDAO::STRING_TYPE,
        ],
        'last_modified' => [
            'label' => 'Last Modified',
            'type'  => NormalizedValueDAO::DATETIME_TYPE,
        ],
    ];

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    /**
     * ExampleSyncDataExchange constructor.
     */
    public function __construct()
    {
        // Using the default normalizer for this example but each integration may need it's own if
        // it needs/has data formatted in a unique way
        $this->valueNormalizer = new ValueNormalizer();
    }

    /**
     * This pushes to the integration objects that were updated/created in Mautic. The "sync order" is
     * created by the SyncProcess service.
     *
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO)
    {
        $payload = ['create' => [], 'update' => []];
        $byEmail = [];

        $orderedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($orderedObjects as $objectName => $unidentifiedObjects) {
            /**
             * @var mixed           $key
             * @var ObjectChangeDAO $unidentifiedObject
             */
            foreach ($unidentifiedObjects as $unidentifiedObject) {
                $fields = $unidentifiedObject->getFields();

                // Extract identifier fields for this integration to check if they exist before creating
                // Some integrations offer a upsert feature which may make this not necessary.
                $emailAddress = $unidentifiedObject->getField('email')->getValue()->getNormalizedValue();
                // Store by email address so they can be found again when we update the OrderDAO about mapping
                $byEmail[$emailAddress] = $unidentifiedObject;

                // Build the person's profile
                $person = ['object' => $objectName];
                foreach ($fields as $field) {
                    $person[$field->getName()] = $this->valueNormalizer->normalizeForIntegration($field->getValue());
                }

                // Create by default because it is unknown if they exist upstream or not
                $payload['create'][$emailAddress] = $person;
            }

            // If applicable, do something to verify if email addresses exist and if so, update objects instead
            // $api->searchByEmail(array_keys($byEmail));
        }

        $orderedObjects = $syncOrderDAO->getIdentifiedObjects();
        foreach ($orderedObjects as $objectName => $identifiedObjects) {
            /**
             * @var mixed           $key
             * @var ObjectChangeDAO $unidentifiedObject
             */
            foreach ($identifiedObjects as $id => $identifiedObject) {
                $fields = $unidentifiedObject->getFields();

                // Build the person's profile
                $person = [
                    'id'     => $id,
                    'object' => $objectName
                ];
                foreach ($fields as $field) {
                    $person[$field->getName()] = $this->valueNormalizer->normalizeForIntegration($field->getValue());
                }

                $payload['update'][$key] = $person;
            }
        }

        return;

        //@todo
        // Deliver payload and get response
        $response = [
            'results' => [
                [
                    'result'     => 'created',
                    'id'         => 'lead_1',
                    'first_name' => 'John',
                    'last_name'  => 'Smith',
                    'email'      => 'john.smith@lead.com',
                    'object'     => 'Lead',
                ], // etc
            ]
        ];

        // Notify the order regarding IDs of created objects
        foreach ($response['results'] as $result) {
            /** @var ObjectChangeDAO $object */
            $object = $byEmail[$result['email']];

            $syncOrderDAO->addEntityMapping(
                new EntityMappingDAO(
                    $object->getMappedObject(),
                    $object->getMappedId(),
                    $result['object'],
                    $result['id']
                )
            );
        }
    }

    /**
     * This fetches objects from the integration that needs to be updated or created in Mautic.
     * A "sync report" is created that will be processed by the SyncProcess service.
     *
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO)
    {
        // Build a report of objects that have been modified
        $syncReport = new ReportDAO(ExampleIntegration::NAME);

        $requestedObjects = $requestDAO->getObjects();
        foreach ($requestedObjects as $requestedObject) {
            $objectName    = $requestedObject->getObject();
            $fromTimestamp = $requestDAO->getFromTimestamp();
            $toTimestamp   = $requestDAO->getToTimestamp();
            $mappedFields  = $requestedObject->getFields();

            $updatedPeople = $this->getPayload($objectName, $fromTimestamp, $toTimestamp, $mappedFields);
            foreach ($updatedPeople as $person) {
                // If the integration knows modified timestamps per field, use that. Otherwise, we're using the complete object's
                // last modified timestamp.
                $objectChangeTimestamp = strtotime($person['last_modified']);

                $objectDAO = new ObjectDAO($objectName, $person['id']);
                $objectDAO->setChangeTimestamp($objectChangeTimestamp);

                foreach ($person as $field => $value) {
                    // Normalize the value from the API to what Mautic needs
                    $normalizedValue = $this->valueNormalizer->normalizeForMautic(self::FIELDS[$field]['type'], $value);
                    $reportFieldDAO  = new FieldDAO($field, $normalizedValue);

                    // If we know for certain that this specific field was modified at a specific date/time, set the change timestamp
                    // on the field itself for the judge to weigh certain versus possible changes
                    //$reportFieldDAO->setChangeTimestamp($fieldChangeTimestamp);

                    $objectDAO->addField($reportFieldDAO);
                }

                $syncReport->addObject($objectDAO);
            }
        }

        return $syncReport;
    }

    /**
     * @param       $object
     * @param       $fromDateTime
     * @param       $toTimestamp
     * @param array $mappedFields
     *
     * @return mixed
     */
    private function getPayload($object, $fromDateTime, $toTimestamp, array $mappedFields)
    {
        // Query integration's API for objects changed between $fromDateTime and $toTimestamp with the requested fields in $mappedFields if that's
        // applicable to the integration. I.e. Salesforce supports querying for specific fields in it's SOQL

        $payload = [
            self::CONTACT_OBJECT => [
                [
                    'id'            => 1,
                    'first_name'    => 'John',
                    'last_name'     => 'Smith',
                    'email'         => 'john.smith@contact.com',
                    'last_modified' => '2018-08-02T10:02:00+05:00',
                ],
                [
                    'id'            => 2,
                    'first_name'    => 'Jane',
                    'last_name'     => 'Smith',
                    'email'         => 'Jane.smith@contact.com',
                    'last_modified' => '2018-08-02T10:07:00+05:00',
                ],
            ],
            self::LEAD_OBJECT    => [
                [
                    'id'            => 3,
                    'first_name'    => 'John',
                    'last_name'     => 'Smith',
                    'email'         => 'john.smith@lead.com',
                    'last_modified' => '2018-08-02T10:02:00+05:00',
                ],
                [
                    'id'            => 4,
                    'first_name'    => 'Jane',
                    'last_name'     => 'Smith',
                    'email'         => 'Jane.smith@lead.com',
                    'last_modified' => '2018-08-02T10:07:00+05:00',
                ],
            ],
        ];

        return $payload[$object];
    }
}