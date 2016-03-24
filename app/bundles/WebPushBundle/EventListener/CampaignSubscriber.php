<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticWebPushBundle
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
        if ($this->factory->getParameter('webpush_enabled')) {
            $event->addAction(
                'webpush.send_webpush_notification',
                array(
                    'label' => 'mautic.webpush.campaign.send_webpush_notification',
                    'description' => 'mautic.webpush.campaign.send_webpush_notification.tooltip',
                    'callback' => array('\Mautic\WebPushBundle\Helper\WebPushHelper', 'send'),
                    'formType' => 'webpush'
                )
            );
        }
    }
}