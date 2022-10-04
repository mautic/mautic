<?php

namespace Mautic\CampaignBundle\EventCollector\Accessor\Event;

class DecisionAccessor extends AbstractEventAccessor
{
    /**
     * DecisionAccessor constructor.
     */
    public function __construct(array $config)
    {
        $this->systemProperties[] = 'eventName';

        parent::__construct($config);
    }

    /**
     * @return string
     */
    public function getEventName()
    {
        return $this->getProperty('eventName');
    }
}
