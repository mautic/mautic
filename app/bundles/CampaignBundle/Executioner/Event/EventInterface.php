<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Executioner\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;

interface EventInterface
{
    /**
     * @param AbstractEventAccessor $config
     * @param ArrayCollection       $logs
     *
     * @return mixed
     */
    public function executeLogs(AbstractEventAccessor $config, ArrayCollection $logs);
}
