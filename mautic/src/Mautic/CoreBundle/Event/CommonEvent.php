<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Doctrine\ORM\EntityManager;

/**
 * Class CommonEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class CommonEvent extends Event
{
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }
}