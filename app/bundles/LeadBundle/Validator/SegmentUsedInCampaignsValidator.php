<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentUsedInCampaignsValidator
{
    private string $errorMessage = '';

    public function __construct(private LeadListRepository $leadListRepository, private TranslatorInterface $translator)
    {
    }

    public function validate(LeadList $segment, string $action = 'unpublish'): bool
    {
        if (!$segment->getId()) {
            return false;
        }

        $campaignNames = $this->leadListRepository->getSegmentCampaigns($segment->getId());
        if (1 > count($campaignNames)) {
            return false;
        }

        $campaignNamesQuotes = array_map(fn (string $campaignName): string => sprintf('"%s"', $campaignName), $campaignNames);
        $campaignNamesCsv    = implode(', ', $campaignNamesQuotes);

        $this->errorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.'.$action,
            [
                '%campaignNames%' => $campaignNamesCsv,
                '%segmentNames%'  => $segment->getName(),
                '%count%'         => count($campaignNames),
            ],
            'validators'
        );

        return true;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
