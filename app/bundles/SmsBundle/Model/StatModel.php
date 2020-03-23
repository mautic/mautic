<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Model;

use Mautic\SmsBundle\Callback\Event\DeliveryCallbackEvent;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;

class StatModel
{
    /**
     * @var SmsModel
     */
    private $smsModel;

    /**
     * @var Sms
     */
    private $sms;

    /**
     * @var Stat
     */
    private $stat;

    /**
     * @var DeliveryCallbackEvent
     */
    private $deliveryCallbackEvent;

    /**
     * StatModel constructor.
     *
     * @param SmsModel $smsModel
     */
    public function __construct(SmsModel $smsModel)
    {
        $this->smsModel = $smsModel;
    }

    /**
     * @param DeliveryCallbackEvent $deliveryCallbackEvent
     */
    public function updateStatsFromDeliveryCallbackEvent(DeliveryCallbackEvent $deliveryCallbackEvent)
    {
        $smsStatRepository = $this->smsModel->getStatRepository();

        $this->stat                  =  $smsStatRepository->findOneBy(['trackingHash' => $deliveryCallbackEvent->getTrackingHash()]);
        $this->sms                   = $this->stat->getSms();
        $this->deliveryCallbackEvent = $deliveryCallbackEvent;

        if ($this->deliveryCallbackEvent->isDelivered()) {
            $this->setAsDeliveredAndUpCount();
        } elseif ($this->deliveryCallbackEvent->isRead()) {
            $this->setAsReadAndUpCount();
        } elseif ($this->deliveryCallbackEvent->isFailed()) {
            $this->setAsFailedAndUpCount();
        } else {
            return;
        }

        $smsStatRepository->saveEntity($this->stat);

        // If SMS entity changed
        if (!empty($this->sms->getChanges())) {
            $this->smsModel->getRepository()->saveEntity($this->sms);
        }
    }

    /**
     * Pretend up count for already delivered messages.
     */
    private function setAsDeliveredAndUpCount()
    {
        if (!$this->stat->isDelivered()) {
            $this->sms->setDeliveredCount($this->sms->getDeliveredCount() + 1);
        }
        $this->stat->setIsDelivered(true);
    }

    /**
     * Pretend up count for already delivered and read messages
     * If is read, then is also delivered If not done before
     * First try update counter, then mark message as delivered and read.
     */
    public function setAsReadAndUpCount()
    {
        $this->setAsDeliveredAndUpCount();
        if (!$this->stat->isRead()) {
            $this->sms->setReadCount($this->sms->getReadCount() + 1);
        }
        $this->stat->setIsRead(true);
    }

    /**
     * Pretend up count for already failed messages.
     */
    public function setAsFailedAndUpCount()
    {
        if (!$this->stat->isFailed()) {
            $this->sms->setFailedCount($this->sms->getFailedCount() + 1);
        }
        $this->stat->setIsFailed(true);
    }
}
