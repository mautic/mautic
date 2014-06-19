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
     * Determines changes to original entity
     *
     * @param array $ignore
     * @return mixed
     */
    public function getChanges($ignore = array())
    {
        if ($this->em === null) {
            throw new NotAcceptableHttpException('EntityManager not set. Did you forget to set it with $event->setEntityManager()?');
        }

        if ($this->isNew) {
            $changeset = array('entity' => $this->entity);
        } else {
            if (!empty($this->entity)) {
                $uow = $this->em->getUnitOfWork();
                $uow->computeChangeSets();
                $changeset = array('changes' => $uow->getEntityChangeSet($this->entity));
            } else {
                $changeset = array();
            }
        }

        //remove timestamps and the like
        $ignore = array_merge($ignore, array(
            'dateModified',
            'modifiedBy',
            'dateAdded',
            'createdBy',
            'checkedOut',
            'checkedOutBy'
        ));

        foreach ($ignore as $r) {
            if (isset($changeset[$r])) {
                unset($changeset[$r]);
            }
        }

        return $changeset;
    }
}