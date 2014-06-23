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

/**
 * Class CommonEvent
 *
 * @package Mautic\CoreBundle\Event
 */
class CommonEvent extends Event
{

    /**
     * @var
     */
    protected $em;

    /**
     * @var
     */
    protected $entity;

    /**
     * @var
     */
    protected $isNew = true;

    /**
     * Sets the entity manager for the event to use
     *
     * @param $em
     */
    public function setEntityManager($em)
    {
        $this->em = $em;
    }

    /**
     * Returns if a saved lead is new or not
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * Gets changes to original entity
     *
     * @return mixed
     */
    public function getChanges()
    {
        return ($this->entity && method_exists($this->entity, 'getChanges')) ? $this->entity->getChanges() : false;
    }
}