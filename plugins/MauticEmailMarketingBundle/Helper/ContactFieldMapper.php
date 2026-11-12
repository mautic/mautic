<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact as DoNotContactModel;
use Mautic\PluginBundle\Helper\Cleaner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Builds the data pushed to an email marketing service from a push action's field mapping.
 */
class ContactFieldMapper
{
    public const string CONTACT_ID = 'mauticContactId';

    public const string CONTACT_TIMELINE_LINK = 'mauticContactTimelineLink';

    public const string CONTACT_IS_CONTACTABLE_BY_EMAIL = 'mauticContactIsContactableByEmail';

    public function __construct(
        private readonly UrlGeneratorInterface $router,
        private readonly DoNotContactModel $doNotContactModel,
    ) {
    }

    /**
     * Reads a mapping saved by the current push settings form (integration field => Mautic field)
     * or by the former field matching form (i_1/m_1 pairs).
     *
     * @param array<mixed> $leadFields
     *
     * @return array<string, string> Mautic field by integration field
     */
    public static function normalize(array $leadFields): array
    {
        if (isset($leadFields['m_1'])) {
            $formatted = [];
            $rows      = count($leadFields) / 3;
            for ($i = 1; $i < $rows; ++$i) {
                if (isset($leadFields['i_'.$i], $leadFields['m_'.$i])) {
                    $formatted[$leadFields['i_'.$i]] = $leadFields['m_'.$i];
                }
            }

            if ([] !== $formatted) {
                $leadFields = $formatted;
            }
        }

        $mapping = [];
        foreach ($leadFields as $integrationField => $mauticField) {
            if (is_string($mauticField) && '' !== $mauticField) {
                $mapping[(string) $integrationField] = $mauticField;
            }
        }

        return $mapping;
    }

    /**
     * @param array<string, string> $mapping Mautic field by integration field
     *
     * @return array<string, string> value by integration field; empty values are left out
     */
    public function map(Lead $contact, array $mapping, string $integrationName): array
    {
        $fields = $contact->getProfileFields();
        $data   = [];

        foreach ($mapping as $integrationField => $mauticField) {
            $value = match ($mauticField) {
                self::CONTACT_ID                      => (string) $contact->getId(),
                self::CONTACT_TIMELINE_LINK           => $this->router->generate(
                    'mautic_plugin_timeline_view',
                    ['integration' => $integrationName, 'leadId' => $contact->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ),
                self::CONTACT_IS_CONTACTABLE_BY_EMAIL => DoNotContact::IS_CONTACTABLE === $this->doNotContactModel->isContactable($contact, 'email') ? '0' : '1',
                default                               => isset($fields[$mauticField]) && '' !== $fields[$mauticField]
                    ? (string) Cleaner::clean((string) $fields[$mauticField])
                    : null,
            };

            if (null !== $value) {
                $data[$integrationField] = $value;
            }
        }

        return $data;
    }
}
