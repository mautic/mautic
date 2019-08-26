<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventCollector\Accessor\Event;

class DecisionAccessor extends AbstractEventAccessor
{
    /**
     * DecisionAccessor constructor.
     *
     * @param array $config
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
