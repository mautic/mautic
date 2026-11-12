<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\PushContactInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEmailMarketingBundle\Client\ConstantContactClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConstantContactIntegration extends BasicIntegration implements BasicInterface, PushContactInterface
{
    public const string NAME = 'ConstantContact';

    public const array FIELDS = [
        'email',
        'prefix_name',
        'first_name',
        'last_name',
        'company_name',
        'job_title',
        'address_line1',
        'address_line2',
        'address_city',
        'address_state',
        'address_country_code',
        'address_postal_code',
        'cell_phone',
        'fax',
        'work_phone',
        'home_phone',
    ];

    public const int CUSTOM_FIELD_COUNT = 15;

    public function __construct(
        private readonly ConstantContactClient $client,
        private readonly ContactFieldMapper $contactFieldMapper,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return 'Constant Contact';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticEmailMarketingBundle/Assets/img/constantcontact.png';
    }

    public function getPushContactConfigFormName(): string
    {
        return ConstantContactType::class;
    }

    public function pushContact(Lead $contact, array $config): void
    {
        $listSettings = $config['list_settings'] ?? null;
        if (!is_array($listSettings) || empty($listSettings['list'])) {
            throw new PushContactException($this->translator->trans('mautic.emailmarketing.push.no_list', ['%integration%' => $this->getDisplayName()]));
        }

        $data = $this->contactFieldMapper->map($contact, ContactFieldMapper::normalize((array) ($listSettings['leadFields'] ?? [])), self::NAME);
        if (empty($data['email'])) {
            throw new PushContactException($this->translator->trans('mautic.emailmarketing.push.no_email', ['%integration%' => $this->getDisplayName()]));
        }

        $email = $data['email'];
        unset($data['email']);

        // Kept as the former integration had it: the prefixes are checked on the values, not on the field names
        $addresses    = [];
        $customFields = [];
        foreach ($data as $k => $v) {
            if (str_starts_with($v, 'address_')) {
                $addresses[str_replace('address_', '', $k)] = $v;
                unset($data[$k]);
            } elseif (str_starts_with($v, 'customfield_')) {
                $customFields[] = [
                    'name'  => str_replace('customfield_', 'CustomField', $k),
                    'value' => $v,
                ];
                unset($data[$k]);
            }
        }

        if ([] !== $addresses) {
            $addresses['address_type'] = 'PERSONAL';
            $data['addresses']         = $addresses;
        }

        if ([] !== $customFields) {
            $data['custom_fields'] = $customFields;
        }

        try {
            $this->client->subscribe(
                $this->getIntegrationConfiguration()->getApiKeys(),
                (string) $listSettings['list'],
                $email,
                $data,
                empty($listSettings['sendWelcome']) ? 'ACTION_BY_OWNER' : 'ACTION_BY_VISITOR'
            );
        } catch (ApiException $e) {
            throw new PushContactException($e->getMessage(), 0, $e);
        }
    }
}
