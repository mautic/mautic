<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\CampaignEvents;

/**
 * Class CampaignSubscriber
 *
 * @package MauticSmsBundle
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
        if ($this->factory->getParameter('sms_enabled')) {
            $event->addAction(
                'sms.send_text_sms',
                array(
                    'label' => 'mautic.sms.campaign.send_sms_sms',
                    'description' => 'mautic.sms.campaign.send_sms_sms.tooltip',
                    'callback' => array('\Mautic\SmsBundle\Helper\SmsHelper', 'send'),
                    'formType' => 'sms'
                )
            );
        }
    }
}