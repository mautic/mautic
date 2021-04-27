<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator;

use Mautic\CoreBundle\Exception\RecordNotUnpublishedException;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Component\Translation\TranslatorInterface;

class SegmentUsedInCampaignsValidator
{
    /**
     * @var LeadListRepository
     */
    private $leadListRepository;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(LeadListRepository $leadListRepository, TranslatorInterface $translator)
    {
        $this->leadListRepository = $leadListRepository;
        $this->translator         = $translator;
    }

    /**
     * @throws RecordNotUnpublishedException
     */
    public function validate(LeadList $segment): void
    {
        if (!$segment->getId()) {
            return;
        }

        $segments = $this->leadListRepository->getSegmentCampaigns($segment->getId());
        if (1 > count($segments)) {
            return;
        }

        $campaignNames = array_map([$this, 'decorateCampaignName'], $segments);
        $campaignNames = implode(', ', $campaignNames);

        $errorMessage = $this->translator->transChoice(
            'mautic.lead.lists.used_in_campaigns',
            count($segments),
            [
                '%campaignNames%' => $campaignNames,
            ],
            'validators'
        );

        throw new RecordNotUnpublishedException($errorMessage);
    }

    private function decorateCampaignName($campaignName): string
    {
        return sprintf('"%s"', $campaignName);
    }
}
