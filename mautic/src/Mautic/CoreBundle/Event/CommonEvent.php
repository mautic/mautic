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
     * @param EntityManager $em
     */
    public function setEntityManager(EntityManager $em)
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
     * Determines changes to original entity
     *
     * @return mixed
     */
    public function getChanges()
    {
        if (!$this->em instanceof EntityManager) {
            throw new NotAcceptableHttpException('EntityManager not set. Did you forget to set it with $event->setEntityManager()?');
        }

        if ($this->isNew) {
            $changeset = $this->entity;
        } else {
            if (!empty($this->entity)) {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets();
                $changeset = $uow->getEntityChangeSet($this->entity);
            } else {
                $changeset = array();
            }
        }

        return $changeset;
    }
}