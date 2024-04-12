<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator;

use Mautic\CoreBundle\Exception\RecordNotUnpublishedException;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentUsedInCampaignsValidator
{
    public function __construct(private LeadListRepository $leadListRepository, private TranslatorInterface $translator)
    {
    }

    /**
     * @throws RecordNotUnpublishedException
     */
    public function validate(LeadList $segment): void
    {
        if (!$segment->getId()) {
            return;
        }

        $campaignNames = $this->leadListRepository->getSegmentCampaigns($segment->getId());

        if (1 > count($campaignNames)) {
            return;
        }

        $campaignNames = array_map(fn (string $segmentName): string => sprintf('"%s"', $segmentName), $campaignNames);
        $errorMessage  = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns',
            [
                '%count%'         => count($campaignNames),
                '%campaignNames%' => implode(', ', $campaignNames),
            ],
            'validators'
        );

        throw new RecordNotUnpublishedException($errorMessage);
    }
}
