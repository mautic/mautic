<?php

declare(strict_types=1);

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
     * @throws NoListenerException
     */
    public function dispatchPreSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_PRE_SAVE, $entity, $isNew);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_POST_SAVE, $entity, $isNew);
    }

    /**
     * @deprecated Use method dispatchEvent directly
     */
    public function dispatchEventBc(string $action, LeadField $entity, bool $isNew = false, LeadFieldEvent $event = null): ?LeadFieldEvent
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
     *
     * @throws NoListenerException
     */
    private function dispatchEvent(string $action, LeadField $entity, bool $isNew, LeadFieldEvent $event = null): LeadFieldEvent
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
