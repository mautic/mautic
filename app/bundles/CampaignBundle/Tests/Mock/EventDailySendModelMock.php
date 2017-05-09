<?php

namespace Mautic\CampaignBundle\Tests\Mock;

use Mautic\CampaignBundle\Model\EventDailySendModel;

/**
 * Class EventDailySendModelMock.
 */
class EventDailySendModelMock extends EventDailySendModel
{
    private $date = null;

    public function getRepository()
    {
        return new RepositoryMock();
    }

    protected function getDate()
    {
        if ($this->date) {
            return $this->date;
        }

        return new \Datetime();
    }

    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }
}
