<?php

namespace Mautic\EmailBundle\Tests\Mock;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Model\EventModel;

/**
 * Class CampaignSubscriberTest.
 */
class EventModelMock extends EventModel
{
    private $entity = null;

    public function getEntity($id = null)
    {
        if ($this->entity) {
            return $this->entity;
        }

        if ($id) {
            return new Event($id);
        }

        return new Event();
    }

    public function setEntity($entity)
    {
        $this->entity = $entity;
    }
}
