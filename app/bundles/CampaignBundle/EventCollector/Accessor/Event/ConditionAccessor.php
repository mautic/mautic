<?php

namespace Mautic\CampaignBundle\EventCollector\Accessor\Event;

/**
 * Class ConditionAccessor.
 */
class ConditionAccessor extends AbstractEventAccessor
{
    /**
     * ConditionAccessor constructor.
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
