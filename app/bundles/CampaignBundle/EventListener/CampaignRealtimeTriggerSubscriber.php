<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignLeadChangeEvent;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignRealtimeTriggerSubscriber implements EventSubscriberInterface
{
    /**
     * @var KickoffExecutioner
     */
    private $kickoffExecutioner;

    /**
     * CampaignRealtimeTriggerSubscriber constructor.
     *
     * @param KickoffExecutioner $kickoffExecutioner
     */
    public function __construct(KickoffExecutioner $kickoffExecutioner)
    {
        $this->kickoffExecutioner = $kickoffExecutioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_LEADCHANGE         => ['onCampaignLeadChange', -1],
            CampaignEvents::LEAD_CAMPAIGN_BATCH_CHANGE     => ['onCampaignLeadChange', -2],
        ];
    }

    /**
     * @param CampaignLeadChangeEvent $event
     */
    public function onCampaignLeadChange(CampaignLeadChangeEvent $event)
    {
        $leads = $event->getLeads();
        // If not batch change
        if (empty($leads)) {
            $leads = [$event->getLead()];
        }
        foreach ($leads as $lead) {
            if ($event->getCampaign()->isTriggerRealtime() && $event->wasAdded()) {
                $contactLimiterer = new ContactLimiter(null, $lead->getId());
                $this->kickoffExecutioner->execute($event->getCampaign(), $contactLimiterer);
            }
        }
    }
}
