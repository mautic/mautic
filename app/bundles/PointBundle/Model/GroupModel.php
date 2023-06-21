<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupRepository;
use Mautic\PointBundle\Event as Events;
use Mautic\PointBundle\Form\Type\GroupType;
use Mautic\PointBundle\GroupEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends CommonFormModel<Group>
 */
class GroupModel extends CommonFormModel
{
    public function getRepository(): GroupRepository
    {
        return $this->em->getRepository(Group::class);
    }

    public function getPermissionBase(): string
    {
        return 'point:groups';
    }

    /**
     * {@inheritdoc}
     *
     * @param object               $entity
     * @param FormFactory          $formFactory
     * @param string|null          $action
     * @param array<string,string> $options
     *
     * @return mixed
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof Group) {
            throw new MethodNotAllowedHttpException(['Group']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(GroupType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param int $id
     *
     * @return object|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Group();
        }

        return parent::getEntity($id);
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = null)
    {
        if (!$entity instanceof Group) {
            throw new MethodNotAllowedHttpException(['Group']);
        }

        switch ($action) {
            case 'pre_save':
                $name = GroupEvents::GROUP_PRE_SAVE;
                break;
            case 'post_save':
                $name = GroupEvents::GROUP_POST_SAVE;
                break;
            case 'pre_delete':
                $name = GroupEvents::GROUP_PRE_DELETE;
                break;
            case 'post_delete':
                $name = GroupEvents::GROUP_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\GroupEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }
}
