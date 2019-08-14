<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use Mautic\SmsBundle\Callback\DAO\DeliveryStatusDAO;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\Stat;

class StatCountHelper
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
     * @var DeliveryStatusDAO
     */
    private $deliveryStatusDAO;

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
     * @param DeliveryStatusDAO $deliveryStatusDAO
     */
    public function updateStatsFromDeliveryStatusDAO(DeliveryStatusDAO $deliveryStatusDAO)
    {
        $smsStatRepository = $this->smsModel->getStatRepository();

        $this->stat              = $stat              =  $smsStatRepository->findOneBy(['trackingHash' => $deliveryStatusDAO->getTrackingHash()]);
        $this->sms               = $sms               = $this->stat->getSms();
        $this->deliveryStatusDAO = $deliveryStatusDAO;

        if ($this->deliveryStatusDAO->isDelivered()) {
            $this->setAsDeliveredAndUpCount();
        } elseif ($this->deliveryStatusDAO->isRead()) {
            $this->setAsReadAndUpCount();
        } elseif ($this->deliveryStatusDAO->isFailed()) {
            $this->setAsFailedandUpCount();
        }

        // If Stat entity changed, save it
        if ($stat != $this->stat) {
            $smsStatRepository->saveEntity($this->stat);
        }

        // If SMS entity changed
        if ($sms != $this->sms) {
            $this->smsModel->getRepository()->saveEntity($this->sms);
        }
    }

    /**
     * Pretend up count for already delivered messages.
     */
    private function setAsDeliveredAndUpCount()
    {
        if (!$this->stat->isDelivered()) {
            $this->sms->setDeliveriedCount($this->sms->getDeliveriedCount() + 1);
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
    public function setAsFailedandUpCount()
    {
        if (!$this->stat->setIsFailed()) {
            $this->sms->setFailedCount($this->sms->getReadCount() + 1);
        }
        $this->stat->setIsFailed(true);
    }
}
