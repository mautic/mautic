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

        $segments = $this->leadListRepository->getSegmentCampaigns($segment->getId());
        if (1 > count($segments)) {
            return false;
        }

        $campaignNames      = array_map([$this, 'decorateCampaignName'], $segments);
        $campaignNames      = implode(', ', $campaignNames);
        $this->errorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.'.$action,
            [
                '%campaignNames%' => $campaignNames,
                '%segmentNames%'  => $segment->getName(),
                '%count%' => count($segments),
            ],
            'validators'
        );

        return true;
    }

    private function decorateCampaignName($campaignName): string
    {
        return sprintf('"%s"', $campaignName);
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
