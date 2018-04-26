<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\LeadBundle\Deduplicate\ContactMerger;
use Mautic\LeadBundle\Deduplicate\Exception\SameContactException;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class LegacyLeadModel.
 *
 * @deprecated 2.14.0 to be removed in 3.0; Used temporarily to get around circular depdenency for LeadModel
 */
class LegacyLeadModel
{
    /**
     * @var Container
     */
    private $container;

    /**
     * LegacyContactMerger constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Lead $lead
     * @param Lead $lead2
     * @param bool $autoMode
     *
     * @return Lead
     */
    public function mergeLeads(Lead $lead, Lead $lead2, $autoMode = true)
    {
        $leadId = $lead->getId();

        if ($autoMode) {
            //which lead is the oldest?
            $winner = ($lead->getDateAdded() < $lead2->getDateAdded()) ? $lead : $lead2;
            $loser  = ($winner->getId() === $leadId) ? $lead2 : $lead;
        } else {
            $winner = $lead2;
            $loser  = $lead;
        }

        try {
            /** @var ContactMerger $contactMerger */
            $contactMerger = $this->container->get('mautic.lead.merger');

            return $contactMerger->merge($winner, $loser);
        } catch (SameContactException $exception) {
            return $lead;
        }
    }
}
