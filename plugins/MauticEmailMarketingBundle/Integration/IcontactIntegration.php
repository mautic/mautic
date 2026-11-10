<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\PushContactInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEmailMarketingBundle\Client\IcontactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\IcontactType;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;

class IcontactIntegration extends BasicIntegration implements BasicInterface, PushContactInterface
{
    public const string NAME = 'Icontact';

    public const array FIELDS = [
        'email',
        'prefix',
        'firstName',
        'lastName',
        'suffix',
        'street',
        'street2',
        'city',
        'state',
        'postalCode',
        'phone',
        'fax',
        'business',
    ];

    /**
     * Prefix of the mapped custom fields, followed by the iContact custom field ID.
     */
    public const string CUSTOM_FIELD_PREFIX = 'cf_';

    public function __construct(
        private readonly IcontactClient $client,
        private readonly ContactFieldMapper $contactFieldMapper,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'iContact';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticEmailMarketingBundle/Assets/img/icontact.png';
    }

    public function getPushContactConfigFormName(): string
    {
        return IcontactType::class;
    }

    public function pushContact(Lead $contact, array $config): void
    {
        $listSettings = $config['list_settings'] ?? null;
        if (!is_array($listSettings) || empty($listSettings['list'])) {
            throw new PushContactException('No iContact list is selected.');
        }

        $data = $this->contactFieldMapper->map($contact, ContactFieldMapper::normalize((array) ($listSettings['leadFields'] ?? [])), self::NAME);
        if (empty($data['email'])) {
            throw new PushContactException('The contact has no value for the field mapped to the iContact email.');
        }

        // Custom fields are sent by their iContact ID
        foreach ($data as $field => $value) {
            if (str_starts_with($field, self::CUSTOM_FIELD_PREFIX)) {
                unset($data[$field]);
                $data[substr($field, strlen(self::CUSTOM_FIELD_PREFIX))] = $value;
            }
        }

        try {
            $this->client->subscribe($this->getIntegrationConfiguration()->getApiKeys(), (string) $listSettings['list'], $data);
        } catch (ApiException $e) {
            throw new PushContactException($e->getMessage(), 0, $e);
        }
    }
}
