<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use Mautic\IntegrationsBundle\Exception\PushContactException;
use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\PushContactInterface;
use Mautic\LeadBundle\Entity\Lead;
use MauticPlugin\MauticEmailMarketingBundle\Client\MailchimpClient;
use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\MailchimpType;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailchimpIntegration extends BasicIntegration implements BasicInterface, PushContactInterface
{
    public const string NAME = 'Mailchimp';

    public function __construct(
        private readonly MailchimpClient $client,
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
        return 'MailChimp';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticEmailMarketingBundle/Assets/img/mailchimp.png';
    }

    public function getPushContactConfigFormName(): string
    {
        return MailchimpType::class;
    }

    public function pushContact(Lead $contact, array $config): void
    {
        $listSettings = $config['list_settings'] ?? null;
        if (!is_array($listSettings) || empty($listSettings['list'])) {
            throw new PushContactException($this->translator->trans('mautic.emailmarketing.push.no_list', ['%integration%' => $this->getDisplayName()]));
        }

        $mapping = ContactFieldMapper::normalize((array) ($listSettings['leadFields'] ?? []));
        $data    = $this->contactFieldMapper->map($contact, $mapping, self::NAME);
        if (empty($data['EMAIL'])) {
            throw new PushContactException($this->translator->trans('mautic.emailmarketing.push.no_email', ['%integration%' => $this->getDisplayName()]));
        }

        $email = $data['EMAIL'];
        unset($data['EMAIL']);

        $keys   = $this->getIntegrationConfiguration()->getApiKeys();
        $listId = (string) $listSettings['list'];

        try {
            $missingFields = array_diff_key($mapping, $data, ['EMAIL' => true]);
            if ([] !== $missingFields) {
                // Like the former integration, required merge fields without a value are sent as "Unknown"
                $mergeFields = $this->client->getMergeFields($keys, $listId);
                foreach (array_keys($missingFields) as $tag) {
                    if (!empty($mergeFields[$tag]['required'])) {
                        $data[$tag] = $this->translator->trans('mautic.integration.form.lead.unknown');
                    }
                }
            }

            $this->client->subscribe(
                $keys,
                $listId,
                $email,
                $data,
                (bool) ($listSettings['doubleOptin'] ?? true),
                (bool) ($listSettings['sendWelcome'] ?? true)
            );
        } catch (ApiException $e) {
            throw new PushContactException($e->getMessage(), 0, $e);
        }
    }
}
