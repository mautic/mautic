<?php

namespace Mautic\CampaignBundle\EventCollector\Accessor\Event;

class ActionAccessor extends AbstractEventAccessor
{
    /**
     * ActionAccessor constructor.
     */
    public function __construct(array $config)
    {
        $this->systemProperties[] = 'batchEventName';

        parent::__construct($config);
    }

    /**
     * @return mixed
     */
    public function getBatchEventName()
    {
        return $this->getProperty('batchEventName');
    }
}
