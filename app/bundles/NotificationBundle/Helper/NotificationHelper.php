<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\Helper;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\CoreBundle\Factory\MauticFactory;

class NotificationHelper
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

    /**
     * @param string $email
     *
     * @return bool
     */
    public function unsubscribe($email)
    {
        /** @var \Mautic\LeadBundle\Entity\LeadRepository $repo */
        $repo = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');

        $lead = $repo->getLeadByEmail($email);

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead.lead');

        return $leadModel->addDncForLead($lead, 'notification', null, DoNotContact::UNSUBSCRIBED);
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

        if ($leadModel->isContactable($lead, 'notification') !== DoNotContact::IS_CONTACTABLE) {
            return false;
        }

        // If lead has subscribed on multiple devices, get all of them.
        /** @var \Mautic\NotificationBundle\Entity\PushID[] $pushIDs */
        $pushIDs = $lead->getPushIDs();

        $playerID = array();

        foreach ($pushIDs as $pushID) {
            $playerID[] = $pushID->getPushID();
        }

        /** @var \Mautic\NotificationBundle\Api\OneSignalApi $notification */
        $notification = $factory->getKernel()->getContainer()->get('mautic.notification.api');

        return $notification->sendNotification($playerID, $config['notification_message_template'], $config['notification_message_headings']);
    }
}