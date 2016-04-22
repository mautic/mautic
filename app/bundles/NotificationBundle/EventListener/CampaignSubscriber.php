<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticNotificationBundle
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            CampaignEvents::CAMPAIGN_ON_BUILD => array('onCampaignBuild', 0)
        );
    }

    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        if ($this->factory->getParameter('notification_enabled')) {
            $event->addAction(
                'notification.send_notification',
                array(
                    'label'           => 'mautic.notification.campaign.send_notification',
                    'description'     => 'mautic.notification.campaign.send_notification.tooltip',
                    'callback'        => array('\Mautic\NotificationBundle\Helper\NotificationHelper', 'send'),
                    'formType'        => 'notificationsend_list',
                    'formTypeOptions' => array('update_select' => 'campaignevent_properties_notification'),
                    'formTheme'       => 'MauticNotificationBundle:FormTheme\NotificationSendList',
                    'timelineTemplate'=> 'MauticNotificationBundle:SubscribedEvents\Timeline:index.html.php'

                )
            );
        }
    }
}