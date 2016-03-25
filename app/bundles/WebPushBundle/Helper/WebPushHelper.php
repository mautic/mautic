<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Factory\MauticFactory;

class WebPushHelper
{
    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    public function unsubscribe($number)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');

        $lead = '';

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        return $leadModel->addDncForLead($lead, 'webpush', null, DoNotContact::UNSUBSCRIBED);
    }

    /**
     * @param array $config
     * @param Lead $lead
     * @param MauticFactory $factory
     *
     * @return boolean
     */
    public static function send(array $config, Lead $lead, MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead.lead');

        if ($leadModel->isContactable($lead, 'webpush') !== DoNotContact::IS_CONTACTABLE) {
            return false;
        }

        // If lead has subscribed on multiple channels, get all of them.
        /** @var \Mautic\WebPushBundle\Entity\PushID[] $pushIDs */
        $pushIDs = $lead->getPushIDs();

        $playerID = array();

        foreach ($pushIDs as $pushID) {
            $playerID[] = $pushID->getPushID();
        }

        /** @var \Mautic\WebPushBundle\Api\OneSignalApi $webpush */
        $webpush = $factory->getKernel()->getContainer()->get('mautic.webpush.api');

        return $webpush->sendNotification($playerID, $config['webpush_message_template'], $config['webpush_message_headings']);
    }
}