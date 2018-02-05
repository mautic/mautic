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
    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->systemProperties[] = 'eventName';
    }

    /**
     * @return mixed
     */
    public function getEventName()
    {
        return $this->getProperty('eventName');
    }
}
