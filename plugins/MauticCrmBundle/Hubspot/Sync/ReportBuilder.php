<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;

/**
 * Builds the report of HubSpot objects that changed, one page per sync iteration.
 */
class ReportBuilder
{
    /**
     * HubSpot's search stops at 10,000 results, so a longer pull starts a new search from the last modification date it saw.
     */
    private const int SEARCH_RESULT_LIMIT = 10000;

    /**
     * Where each object's pull continues on the next iteration: the search cursor and the date it searches from.
     * An object that is finished is set to false.
     *
     * @var array<string, array{after: ?string, from: ?\DateTimeInterface}|false>
     */
    private array $cursors = [];

    public function __construct(
        private readonly HubspotClient $client,
        private readonly FieldRepository $fieldRepository,
        private readonly ValueNormalizer $valueNormalizer,
        private readonly ContactExtras $contactExtras,
    ) {
    }

    public function build(RequestDAO $request): ReportDAO
    {
        if (1 === $request->getSyncIteration()) {
            $this->cursors = [];
            $this->contactExtras->clear();
        }

        $report    = new ReportDAO(HubspotIntegration::NAME);
        $objectIds = $request->getInputOptionsDAO()->getIntegrationObjectIds();

        foreach ($request->getObjects() as $requestedObject) {
            $object     = $requestedObject->getObject();
            $properties = $this->getProperties($requestedObject);

            if (null !== $objectIds) {
                // Specific records, e.g. from the webhook, are all read in the first iteration
                $ids     = $objectIds->getObjectIdsFor($object);
                $records = 1 === $request->getSyncIteration() && [] !== $ids ? $this->client->read($object, $ids, $properties) : [];
            } else {
                $records = $this->nextPage($requestedObject, $properties);
            }

            foreach ($records as $record) {
                $report->addObject($this->toReportObject($object, $record, $requestedObject->getFields()));
            }
        }

        return $report;
    }

    /**
     * @param string[] $properties
     *
     * @return list<array<string, mixed>>
     */
    private function nextPage(RequestObjectDAO $requestedObject, array $properties): array
    {
        $object = $requestedObject->getObject();
        $cursor = $this->cursors[$object] ?? ['after' => null, 'from' => $requestedObject->getFromDateTime()];
        if (false === $cursor) {
            return [];
        }

        $page    = $this->client->searchModified($object, $properties, $cursor['from'], $requestedObject->getToDateTime(), $cursor['after']);
        $records = $page['results'];

        if (null === $page['after'] || [] === $records) {
            $this->cursors[$object] = false;
        } elseif ((int) $page['after'] + HubspotClient::BATCH_SIZE > self::SEARCH_RESULT_LIMIT) {
            // Start a new search from the newest date seen; the records modified at that moment come again, which the sync engine handles
            $this->cursors[$object] = ['after' => null, 'from' => $this->getModifiedAt($records[array_key_last($records)])];
        } else {
            $this->cursors[$object] = ['after' => $page['after'], 'from' => $cursor['from']];
        }

        return $records;
    }

    /**
     * @return string[] the mapped properties to fetch, plus the contact extras
     */
    private function getProperties(RequestObjectDAO $requestedObject): array
    {
        $properties = $requestedObject->getFields();
        if (HubspotClient::CONTACTS === $requestedObject->getObject()) {
            $properties = [...$properties, ...ContactExtras::PROPERTIES];
        }

        return array_values(array_unique($properties));
    }

    /**
     * @param array<string, mixed> $record
     * @param string[]             $requestedFields
     */
    private function toReportObject(string $object, array $record, array $requestedFields): ReportObjectDAO
    {
        $id         = (string) $record['id'];
        $properties = is_array($record['properties'] ?? null) ? $record['properties'] : [];
        $fields     = $this->fieldRepository->getFields($object);

        if (HubspotClient::CONTACTS === $object) {
            $this->contactExtras->remember($id, $properties);
        }

        $reportObject = new ReportObjectDAO($object, $id, $this->getModifiedAt($record));
        foreach ($requestedFields as $property) {
            if (!isset($fields[$property])) {
                continue;
            }

            $value = $properties[$property] ?? null;

            $reportObject->addField(new FieldDAO($property, $this->valueNormalizer->toMautic($fields[$property], $value)));
        }

        return $reportObject;
    }

    /**
     * A \DateTime, as the sync engine changes the time zone of the dates it is given.
     *
     * @param array<string, mixed> $record
     */
    private function getModifiedAt(array $record): \DateTime
    {
        try {
            return new \DateTime((string) ($record['updatedAt'] ?? $record['createdAt'] ?? 'now'));
        } catch (\Exception) {
            return new \DateTime();
        }
    }
}
