<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\SmsBundle\Event\DeliveryEvent;
use Mautic\SmsBundle\Helper\StatCountHelper;
use Mautic\SmsBundle\Sms\TransportChain;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeliverySubscriber implements EventSubscriberInterface
{
    /**
     * @var TransportChain
     */
    private $transportChain;

    /**
     * @var StatCountHelper
     */
    private $statCountHelper;

    /**
     * CampaignReplySubscriber constructor.
     *
     * @param TransportChain  $transportChain
     * @param StatCountHelper $statCountHelper
     */
    public function __construct(TransportChain $transportChain, StatCountHelper $statCountHelper)
    {
        $this->transportChain      = $transportChain;
        $this->statCountHelper     = $statCountHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SmsEvents::ON_DELIVERY            => ['onDelivery', 0],
        ];
    }

    /**
     * Update count of Sms and Stat entity.
     *
     * @param DeliveryEvent $event
     */
    public function onDelivery(DeliveryEvent $event)
    {
        $this->statCountHelper->updateStatsFromDeliveryStatusDAO($event->getDeliveryStatusDAO());
    }
}
