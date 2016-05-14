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
     * @return array
     */
    public static function send(array $config, Lead $lead, MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $factory->getModel('lead.lead');
        $logger = $factory->getLogger();

        if ($leadModel->isContactable($lead, 'notification') !== DoNotContact::IS_CONTACTABLE) {
            $logger->error('Error: Lead ' . $lead->getId() . ' is not contactable on the web push channel.');
            return array('failed' => 1);
        }

        // If lead has subscribed on multiple devices, get all of them.
        /** @var \Mautic\NotificationBundle\Entity\PushID[] $pushIDs */
        $pushIDs = $lead->getPushIDs();

        $playerID = array();

        foreach ($pushIDs as $pushID) {
            $playerID[] = $pushID->getPushID();
        }

        if (empty($playerID)) {
            $logger->error('Error: Lead ' . $lead->getId() . ' has not subscribed to web push channel.');
            return array('failed' => 1);
        }

        /** @var \Mautic\NotificationBundle\Api\AbstractNotificationApi $notification */
        $notificationApi = $factory->getKernel()->getContainer()->get('mautic.notification.api');

        /** @var \Mautic\NotificationBundle\Model\NotificationModel $notificationModel */
        $notificationModel = $factory->getModel('notification');
        $notificationId = (int) $config['notification'];

        /** @var \Mautic\NotificationBundle\Entity\Notification $notification */
        $notification = $notificationModel->getEntity($notificationId);

        if ($notification->getId() !== $notificationId) {
            $logger->error('Error: The requested notification cannot be found.');
            return array('failed' => 1);
        }

        $url = $notificationApi->convertToTrackedUrl(
            $notification->getUrl(),
            array(
                'notification' => $notification->getId(),
                'lead' => $lead->getId()
            )
        );

        $response = $notificationApi->sendNotification(
            $playerID,
            $notification->getMessage(),
            $notification->getHeading(),
            $url
        );

        // If for some reason the call failed, tell mautic to try again by return false        
        if ($response->code !== 200) {
            $logger->error('Error: The notification failed to send and returned a ' . $response->code . ' HTTP response with a body of: ' . $response->body);
            return false;
        }

        return array(
            'status' => 'mautic.notification.timeline.status.delivered',
            'type' => 'mautic.notification.notification',
            'id' => $notification->getId(),
            'name' => $notification->getName(),
            'heading' => $notification->getHeading(),
            'content' => $notification->getMessage()
        );
    }
}


