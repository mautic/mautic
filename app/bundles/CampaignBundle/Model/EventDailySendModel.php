<?php

namespace Mautic\CampaignBundle\Model;

use Doctrine\Common\Collections\Criteria;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventDailySendLog;
use Mautic\CoreBundle\Model\FormModel as CommonFormModel;

/**
 * Class EventDailySendModel.
 */
class EventDailySendModel extends CommonFormModel
{
    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(EventDailySendLog::class);
    }

    /**
     * @return \Datetime
     */
    protected function getDate()
    {
        return new \Datetime();
    }

    /**
     * @param Event $event
     *
     * @return EventDailySendLog
     */
    public function getCurrentDayLog(Event $event)
    {
        $log = $event->getDailySendLog()->matching(
            Criteria::create()
                ->where(
                    Criteria::expr()->eq('date', $this->getDate())
                )
        )->first();

        return $log ? $log : $this->createLog($event);
    }

    /**
     * @param Event $event
     *
     * @return EventDailySendLog
     */
    public function createLog(Event $event)
    {
        $log = new EventDailySendLog();
        $log->setEvent($event);

        $this->getRepository()->saveEntity($log);

        return $log;
    }

    /**
     * @param EventDailySendLog $log
     *
     * @return EventDailySendLog
     */
    public function increaseSentCount(EventDailySendLog $log)
    {
        $log->increaseSentCount();
        $this->getRepository()->saveEntity($log);

        return $log;
    }

    /**
     * @param Event             $event
     * @param EventDailySendLog $log
     *
     * @return bool
     */
    public function canBeSend(Event $event, EventDailySendLog $log)
    {
        $dailyLimit = $event->getDailyLimit();

        if ($dailyLimit === 0) {
            return true;
        }

        return $dailyLimit > $log->getSentCount();
    }
}
