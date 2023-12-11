<?php

namespace Mautic\LeadBundle\Twig\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Convert DNC reason ID to text.
 */
final class DncReasonHelper
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @throws UnknownDncReasonException
     */
    public function toText(int $reasonId): string
    {
        $reasonKey = match ($reasonId) {
            DoNotContact::IS_CONTACTABLE => 'mautic.lead.event.donotcontact_contactable',
            DoNotContact::UNSUBSCRIBED   => 'mautic.lead.event.donotcontact_unsubscribed',
            DoNotContact::BOUNCED        => 'mautic.lead.event.donotcontact_bounced',
            DoNotContact::MANUAL         => 'mautic.lead.event.donotcontact_manual',
            default                      => throw new UnknownDncReasonException(sprintf("Unknown DNC reason ID '%c'", $reasonId)),
        };

        return $this->translator->trans($reasonKey);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName(): string
    {
        return 'lead_dnc_reason';
    }
}
