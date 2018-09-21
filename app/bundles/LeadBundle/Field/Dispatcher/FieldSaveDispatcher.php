<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Field\Dispatcher;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldSaveDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EventDispatcherInterface $dispatcher, EntityManager $entityManager)
    {
        $this->dispatcher    = $dispatcher;
        $this->entityManager = $entityManager;
    }

    /**
     * @param bool $isNew
     *
     * @return LeadFieldEvent
     *
     * @throws NoListenerException
     */
    public function dispatchPreSaveEvent(LeadField $entity, $isNew)
    {
        return $this->dispatchEvent(LeadEvents::FIELD_PRE_SAVE, $entity, $isNew);
    }

    /**
     * @param bool $isNew
     *
     * @return LeadFieldEvent
     *
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, $isNew)
    {
        return $this->dispatchEvent(LeadEvents::FIELD_POST_SAVE, $entity, $isNew);
    }

    /**
     * @deprecated Use method dispatchEvent directly
     *
     * @param string $action
     * @param bool   $isNew
     *
     * @return LeadFieldEvent|null
     */
    public function dispatchEventBc($action, LeadField $entity, $isNew = false, LeadFieldEvent $event = null)
    {
        switch ($action) {
            case 'pre_save':
                $name = LeadEvents::FIELD_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeadEvents::FIELD_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeadEvents::FIELD_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeadEvents::FIELD_POST_DELETE;
                break;
            default:
                return null;
        }

        try {
            return $this->dispatchEvent($name, $entity, $isNew, $event);
        } catch (NoListenerException $e) {
            return null;
        }
    }

    /**
     * @param string $action - Use constant from LeadEvents class (e.g. LeadEvents::FIELD_PRE_SAVE)
     * @param bool   $isNew
     *
     * @return LeadFieldEvent
     *
     * @throws NoListenerException
     */
    private function dispatchEvent($action, LeadField $entity, $isNew, LeadFieldEvent $event = null)
    {
        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        if (null === $event) {
            $event = new LeadFieldEvent($entity, $isNew);
            $event->setEntityManager($this->entityManager);
        }

        $this->dispatcher->dispatch($action, $event);

        return $event;
    }
}
