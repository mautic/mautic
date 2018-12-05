<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;

class AllowedEventTypeDecisionEvent extends CommonEvent
{
    /** @var array */
    protected $parentAllowedTypes;

    public function __construct()
    {
        $this->parentAllowedTypes = ['email.send'];
    }

    /**
     * @param string $type
     */
    public function addType($type)
    {
        $this->parentAllowedTypes[] = $type;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->parentAllowedTypes;
    }
}
