<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Templating\Helper;

use http\Exception\InvalidArgumentException;
use Mautic\LeadBundle\Entity\DoNotContact;
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

    /**
     * @param TranslatorInterface $translator
     */
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
     */
    public function toText($reasonId)
    {
        switch ($reasonId) {
            case DoNotContact::UNSUBSCRIBED:
                $reason = $this->translator->trans('mautic.lead.event.donotcontact_unsubscribed');
                break;
            case DoNotContact::BOUNCED:
                $reason = $this->translator->trans('mautic.lead.event.donotcontact_bounced');
                break;
            case DoNotContact::MANUAL:
                $reason = $this->translator->trans('mautic.lead.event.donotcontact_manual');
                break;
            default:
                throw new InvalidArgumentException(
                    sprintf("Unknown DNC reason ID '%i'", $reasonId)
                );
        }

        return $reason;
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
