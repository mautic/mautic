<?php

namespace Mautic\LeadBundle\Templating\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Exception\UnknownDncReasonException;
use Symfony\Component\Templating\Helper\Helper;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Convert DNC reason ID to text.
 */
class DncReasonHelper extends Helper
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Convert DNC reason ID to text.
     *
     * @param int $reasonId
     *
     * @return string
     *
     * @throws UnknownDncReasonException
     */
    public function toText($reasonId)
    {
        switch ($reasonId) {
            case DoNotContact::IS_CONTACTABLE:
                $reasonKey = 'mautic.lead.event.donotcontact_contactable';
                break;
            case DoNotContact::UNSUBSCRIBED:
                $reasonKey = 'mautic.lead.event.donotcontact_unsubscribed';
                break;
            case DoNotContact::BOUNCED:
                $reasonKey = 'mautic.lead.event.donotcontact_bounced';
                break;
            case DoNotContact::MANUAL:
                $reasonKey = 'mautic.lead.event.donotcontact_manual';
                break;
            default:
                throw new UnknownDncReasonException(sprintf("Unknown DNC reason ID '%c'", $reasonId));
        }

        return $this->translator->trans($reasonKey);
    }

    /**
     * Returns the canonical name of this helper.
     *
     * @return string The canonical name
     */
    public function getName()
    {
        return 'lead_dnc_reason';
    }
}
