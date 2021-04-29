<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\EventListener;

use Mautic\SmsBundle\Event\DeliveryEvent;
use Mautic\SmsBundle\Model\StatModel;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeliverySubscriber implements EventSubscriberInterface
{
    /**
     * @var StatModel
     */
    private $statModel;

    /**
     * CampaignReplySubscriber constructor.
     */
    public function __construct(StatModel $statModel)
    {
        $this->statModel      = $statModel;
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
     */
    public function onDelivery(DeliveryEvent $event)
    {
        $this->statModel->updateStatsFromDeliveryEvent($event);
    }
}
