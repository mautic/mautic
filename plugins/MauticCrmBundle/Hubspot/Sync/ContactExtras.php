<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Sync;

/**
 * The lifecycle stage and company of the contacts in the current pull, applied once the sync engine has written them
 * to Mautic. Neither is a contact field the engine can map.
 */
class ContactExtras
{
    public const array PROPERTIES = ['lifecyclestage', 'associatedcompanyid'];

    /**
     * @var array<string, array{stage: ?string, companyId: ?string}> by HubSpot contact ID
     */
    private array $extras = [];

    /**
     * @param array<string, mixed> $properties the contact's HubSpot properties
     */
    public function remember(string $contactId, array $properties): void
    {
        $stage     = $properties['lifecyclestage'] ?? null;
        $companyId = $properties['associatedcompanyid'] ?? null;

        if (empty($stage) && empty($companyId)) {
            unset($this->extras[$contactId]);

            return;
        }

        $this->extras[$contactId] = [
            'stage'     => empty($stage) ? null : (string) $stage,
            'companyId' => empty($companyId) ? null : (string) $companyId,
        ];
    }

    /**
     * @return array{stage: ?string, companyId: ?string}|null
     */
    public function get(string $contactId): ?array
    {
        return $this->extras[$contactId] ?? null;
    }

    public function clear(): void
    {
        $this->extras = [];
    }
}
