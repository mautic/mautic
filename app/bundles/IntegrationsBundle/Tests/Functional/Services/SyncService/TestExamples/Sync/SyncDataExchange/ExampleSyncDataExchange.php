<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Services\SyncService\TestExamples\Sync\SyncDataExchange;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use Mautic\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use Mautic\IntegrationsBundle\Tests\Functional\Services\SyncService\TestExamples\Integration\ExampleIntegration;

class ExampleSyncDataExchange implements SyncDataExchangeInterface
{
    public const OBJECT_LEAD = 'integration_lead';

    /**
     * @var array
     */
    public const FIELDS = [
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
     * @var array
     */
    private $payload = ['create' => [], 'update' => []];

    /**
     * @var ValueNormalizer
     */
    private $valueNormalizer;

    public function __construct()
    {
        // Using the default normalizer for this example but each integration may need it's own if
        // it needs/has data formatted in a unique way
        $this->valueNormalizer = new ValueNormalizer();
    }

    /**
     * This pushes to the integration objects that were updated/created in Mautic. The "sync order" is
     * created by the SyncProcess service.
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $byEmail = [];

        $orderedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($orderedObjects as $objectName => $unidentifiedObjects) {
            /**
             * @var mixed
             * @var ObjectChangeDAO $unidentifiedObject
             */
            foreach ($unidentifiedObjects as $unidentifiedObject) {
                // Use getFields here to ensure we have values for required fields in addition to one way mapped fields
                // Can also use getUnchangedFields, getChangedFields, or getRequiredFields
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
                $this->payload['create'][$emailAddress] = $person;
            }

            // If applicable, do something to verify if email addresses exist and if so, update objects instead to prevent duplicates.
            // This just depends on if the integration has an upsert feature or not.
            // $api->searchByEmail(array_keys($byEmail));
        }

        $orderedObjects = $syncOrderDAO->getIdentifiedObjects();
        foreach ($orderedObjects as $objectName => $identifiedObjects) {
            /**
             * @var mixed
             * @var ObjectChangeDAO $identifiedObject
             */
            foreach ($identifiedObjects as $id => $identifiedObject) {
                // Use getChangedFields in order to update only fields that have been modified since
                $fields = $identifiedObject->getFields();

                // Build the person's profile
                $person = [
                    'id'     => $id,
                    'object' => $objectName,
                ];
                foreach ($fields as $field) {
                    $person[$field->getName()] = $this->valueNormalizer->normalizeForIntegration($field->getValue());
                }

                $this->payload['update'][$id] = $person;
            }
        }

        // Deliver payload and get response
        $response = $this->deliverPayload();

        // Notify the order regarding the status of objects
        foreach ($response as $result) {
            if (empty($byEmail[$result['email']])) {
                continue;
            }

            /** @var ObjectChangeDAO $changeObject */
            $changeObject = $byEmail[$result['email']];

            switch ($result['code']) {
                case 200: // updated
                    $syncOrderDAO->updateLastSyncDate(
                        $changeObject,
                        $result['last_modified']
                    );

                    break;
                case 201: // created
                    $syncOrderDAO->addObjectMapping(
                        $changeObject,
                        $result['object'],
                        $result['id'],
                        $result['last_modified']
                    );

                    break;
                case 404: // assume this object has been deleted
                    $syncOrderDAO->deleteObject($changeObject);

                    break;
                case 405: // simulated "this lead has been converted to a contact"
                    $syncOrderDAO->remapObject(
                        $changeObject->getObject(),
                        $changeObject->getObjectId(),
                        $result['converted_id'],
                        self::OBJECT_LEAD
                    );

                    break;
                case 500: // there was some kind of temporary issue so just retry this later
                    $syncOrderDAO->retrySyncLater($changeObject);

                    break;
                default:
                    // Assume the rest are just failures so don't do anything and the sync process will not continue to sync the objects
            }
        }
    }

    /**
     * This fetches objects from the integration that needs to be updated or created in Mautic.
     * A "sync report" is created that will be processed by the SyncProcess service.
     */
    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        // Build a report of objects that have been modified
        $syncReport = new ReportDAO(ExampleIntegration::NAME);

        if ($requestDAO->getSyncIteration() > 1) {
            // Prevent loop
            return $syncReport;
        }

        $requestedObjects = $requestDAO->getObjects();
        foreach ($requestedObjects as $requestedObject) {
            $objectName   = $requestedObject->getObject();
            $fromDateTime = $requestedObject->getFromDateTime();
            $toDatetime   = $requestedObject->getToDateTime();
            $mappedFields = $requestedObject->getFields();

            $updatedPeople = $this->getReportPayload($objectName, $fromDateTime, $toDatetime, $mappedFields);
            foreach ($updatedPeople as $person) {
                // If the integration knows modified timestamps per field, use that. Otherwise, we're using the complete object's
                // last modified timestamp.
                $objectChangeTimestamp = new \DateTimeImmutable($person['last_modified']);

                $objectDAO = new ObjectDAO($objectName, $person['id'], $objectChangeTimestamp);

                foreach ($person as $field => $value) {
                    // Normalize the value from the API to what Mautic needs
                    $normalizedValue = $this->valueNormalizer->normalizeForMautic(self::FIELDS[$field]['type'], $value);
                    $reportFieldDAO  = new FieldDAO($field, $normalizedValue);

                    // If we know for certain that this specific field was modified at a specific date/time, set the change timestamp
                    // on the field itself for the judge to weigh certain versus possible changes
                    // $reportFieldDAO->setChangeTimestamp($fieldChangeTimestamp);

                    $objectDAO->addField($reportFieldDAO);
                }

                $syncReport->addObject($objectDAO);
            }
        }

        return $syncReport;
    }

    /**
     * @return array
     */
    public function getOrderPayload()
    {
        return $this->payload;
    }

    /**
     * @return mixed
     */
    private function getReportPayload($object, \DateTimeInterface $fromDateTime, \DateTimeInterface $toDateTime, array $mappedFields)
    {
        // Query integration's API for objects changed between $fromDateTime and $toDateTime with the requested fields in $mappedFields if that's
        // applicable to the integration. I.e. Salesforce supports querying for specific fields in it's SOQL

        return [
            [
                'id'            => 1,
                'first_name'    => 'John',
                'last_name'     => 'Contact',
                'email'         => 'john.contact@test.com',
                'last_modified' => '2018-08-02T10:02:00+05:00',
            ],
            [
                'id'            => 2,
                'first_name'    => 'Jane',
                'last_name'     => 'Contact',
                'email'         => 'jane.contact@test.com',
                'last_modified' => '2018-08-02T10:07:00+05:00',
            ],
            [
                'id'            => 3,
                'first_name'    => 'Overwrite',
                'last_name'     => 'Me',
                'email'         => 'NellieABaird@armyspy.com',
                'last_modified' => '2018-08-02T10:02:00+05:00',
            ],
            [
                'id'            => 4,
                'first_name'    => 'Overwrite',
                'last_name'     => 'Me',
                'email'         => 'LewisTSyed@gustr.com',
                'last_modified' => '2018-08-02T10:07:00+05:00',
            ],
        ];
    }

    /**
     * @return array
     */
    private function deliverPayload()
    {
        $now      = new \DateTime('now', new \DateTimeZone('UTC'));
        $response = [];
        $id       = 5;
        foreach ($this->payload['create'] as $person) {
            $person['code']          = 201;
            $person['id']            = $id;
            $person['last_modified'] = $now;
            $response[]              = $person;
            ++$id;
        }

        foreach ($this->payload['update'] as $person) {
            $person['code']          = 200;
            $person['last_modified'] = $now;
            $response[]              = $person;
        }

        return $response;
    }
}
