<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Model;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AbstractCommonModel;
use Mautic\LeadBundle\Entity\Attribution;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class AttributionModel
 */
class AttributionModel extends AbstractCommonModel
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * AttributionModel constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     */
    public function __construct(IpLookupHelper $ipLookupHelper)
    {
       $this->ipLookupHelper = $ipLookupHelper;
    }

    /**
     * @return \Mautic\LeadBundle\Entity\AttributionRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticLeadBundle:Attribution');
    }

    /**
     * Create a new attribution entry
     *
     * @param      $lead
     * @param      $channel
     * @param      $channelId
     * @param      $action
     * @param null $campaignId
     * @param null $amount
     *
     * @return mixed
     */
    public function addAttribution(Lead $lead, $channel, $channelId, $action, $campaignId = null, $amount = null)
    {
        if (empty($amount) && empty($lead->getAttribution())) {

            return false;
        }

        $campaign = null;
        if ($campaignId) {
            $campaign = $this->em->getRepository('MauticCampaignBundle:Campaign')->find($campaignId);
        }

        $attribution = (new Attribution())
            ->setChannel($channel)
            ->setChannelId($channelId)
            ->setAction($action)
            ->setCampaign($campaign)
            ->setLead($lead)
            ->setIpAddress($this->ipLookupHelper->getIpAddress());

        if (null !== $amount) {
            $attribution->setAttribution($amount);
        }

        $this->getRepository()->saveEntity($attribution);

        return $attribution;
    }
}