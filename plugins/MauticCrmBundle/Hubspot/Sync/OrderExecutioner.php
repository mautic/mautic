<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Exception\HubspotApiException;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;

/**
 * Writes the Mautic changes of a sync order to HubSpot.
 *
 * Records already linked to HubSpot are updated. New HubSpot records are created only when the "create records"
 * setting is on or the sync was started by a push action, as HubSpot only received contacts that were pushed before.
 */
class OrderExecutioner
{
    public function __construct(
        private readonly HubspotClient $client,
        private readonly Config $config,
        private readonly FieldRepository $fieldRepository,
        private readonly ValueNormalizer $valueNormalizer,
    ) {
    }

    public function execute(OrderDAO $order): void
    {
        $createRecords = $this->config->createsRecords() || !empty($order->getOptions()[HubspotIntegration::OPTION_CREATE_RECORDS]);

        foreach (array_keys(MappingManualFactory::MAUTIC_OBJECTS) as $object) {
            $identified = $order->getIdentifiedObjects()[$object] ?? [];
            if ([] !== $identified) {
                $this->update($order, $object, $identified);
            }

            $unidentified = $order->getUnidentifiedObjects()[$object] ?? [];
            if ($createRecords && [] !== $unidentified) {
                HubspotClient::CONTACTS === $object ? $this->upsertContacts($order, $unidentified) : $this->createCompanies($order, $unidentified);
            }
        }
    }

    /**
     * @param ObjectChangeDAO[] $changes
     */
    private function update(OrderDAO $order, string $object, array $changes): void
    {
        $changesById    = [];
        $propertiesById = [];
        foreach ($changes as $change) {
            $id         = (string) $change->getObjectId();
            $properties = $this->getProperties($object, $change->getChangedFields());
            if ([] === $properties) {
                $order->updateLastSyncDate($change);
                continue;
            }

            $changesById[$id]    = $change;
            $propertiesById[$id] = $properties;
        }

        if ([] === $propertiesById) {
            return;
        }

        try {
            $response = $this->client->update($object, $propertiesById);
        } catch (HubspotApiException $e) {
            $this->failAll($order, $changesById, $e);

            return;
        }

        foreach ($response['results'] as $result) {
            if (isset($changesById[(string) $result['id']])) {
                $order->updateLastSyncDate($changesById[(string) $result['id']]);
            }
        }

        foreach ($response['errors'] as $error) {
            foreach ($error['context']['ids'] ?? [] as $id) {
                if (!isset($changesById[(string) $id])) {
                    continue;
                }

                if ('OBJECT_NOT_FOUND' === ($error['category'] ?? null)) {
                    // Deleted in HubSpot, so stop syncing it
                    $order->deleteObject($changesById[(string) $id]);
                } else {
                    $order->noteObjectSyncIssue($changesById[(string) $id], (string) ($error['message'] ?? 'HubSpot rejected the update.'));
                }
            }
        }
    }

    /**
     * Contacts are matched by email: an existing HubSpot contact with the email is linked and updated.
     *
     * @param ObjectChangeDAO[] $changes
     */
    private function upsertContacts(OrderDAO $order, array $changes): void
    {
        $changesByEmail    = [];
        $propertiesByEmail = [];
        foreach ($changes as $change) {
            $properties = $this->getProperties(HubspotClient::CONTACTS, [...$change->getRequiredFields(), ...$change->getChangedFields()]);
            $email      = strtolower(trim($properties['email'] ?? ''));
            if ('' === $email) {
                $order->noteObjectSyncIssue($change, 'The contact has no email, which HubSpot requires.');
                continue;
            }

            $changesByEmail[$email]    = $change;
            $propertiesByEmail[$email] = $properties;
        }

        if ([] === $propertiesByEmail) {
            return;
        }

        try {
            $response = $this->client->upsertContactsByEmail($propertiesByEmail);
        } catch (HubspotApiException $e) {
            $this->failAll($order, $changesByEmail, $e);

            return;
        }

        foreach ($response['results'] as $result) {
            $email = strtolower((string) ($result['properties']['email'] ?? ''));
            if (isset($changesByEmail[$email])) {
                $order->addObjectMapping($changesByEmail[$email], HubspotClient::CONTACTS, (string) $result['id']);
                unset($changesByEmail[$email]);
            }
        }

        // HubSpot does not say which input an error belongs to
        $message = (string) ($response['errors'][0]['message'] ?? 'HubSpot did not create the contact.');
        foreach ($changesByEmail as $change) {
            $order->noteObjectSyncIssue($change, $message);
        }
    }

    /**
     * HubSpot has no unique company property to match on, so companies are created one by one.
     *
     * @param ObjectChangeDAO[] $changes
     */
    private function createCompanies(OrderDAO $order, array $changes): void
    {
        foreach ($changes as $change) {
            $properties = $this->getProperties(HubspotClient::COMPANIES, [...$change->getRequiredFields(), ...$change->getChangedFields()]);
            if ([] === $properties) {
                continue;
            }

            try {
                $company = $this->client->create(HubspotClient::COMPANIES, $properties);
            } catch (HubspotApiException $e) {
                $this->failAll($order, [$change], $e);

                continue;
            }

            $order->addObjectMapping($change, HubspotClient::COMPANIES, (string) $company['id']);
        }
    }

    /**
     * @param FieldDAO[] $changedFields
     *
     * @return array<string, string> HubSpot property values
     */
    private function getProperties(string $object, array $changedFields): array
    {
        $fields     = $this->fieldRepository->getFields($object);
        $properties = [];
        foreach ($changedFields as $changedField) {
            $field = $fields[$changedField->getName()] ?? null;
            if (null === $field || !$field->isWritable()) {
                continue;
            }

            $properties[$field->getName()] = $this->valueNormalizer->toHubspot($field, $changedField->getValue());
        }

        return $properties;
    }

    /**
     * @param ObjectChangeDAO[] $changes
     */
    private function failAll(OrderDAO $order, array $changes, HubspotApiException $exception): void
    {
        foreach ($changes as $change) {
            if ($exception->isTemporary()) {
                $order->retrySyncLater($change);
            } else {
                $order->noteObjectSyncIssue($change, $exception->getMessage());
            }
        }
    }
}
