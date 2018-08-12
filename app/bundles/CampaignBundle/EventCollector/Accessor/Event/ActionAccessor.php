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

class ActionAccessor extends AbstractEventAccessor
{
    /**
     * ActionAccessor constructor.
     *
     * @param array $config
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
