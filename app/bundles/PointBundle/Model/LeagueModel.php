<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\League;
use Mautic\PointBundle\Entity\LeagueRepository;
use Mautic\PointBundle\Event as Events;
use Mautic\PointBundle\Form\Type\LeagueType;
use Mautic\PointBundle\LeagueEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

class LeagueModel extends CommonFormModel
{
    public function getRepository(): LeagueRepository
    {
        return $this->em->getRepository(League::class);
    }

    public function getPermissionBase(): string
    {
        return 'point:leagues';
    }

    /**
     * {@inheritdoc}
     *
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
    {
        if (!$entity instanceof League) {
            throw new MethodNotAllowedHttpException(['League']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(LeagueType::class, $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty.
     *
     * @param $id
     *
     * @return object|null
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new League();
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
        if (!$entity instanceof League) {
            throw new MethodNotAllowedHttpException(['League']);
        }

        switch ($action) {
            case 'pre_save':
                $name = LeagueEvents::LEAGUE_PRE_SAVE;
                break;
            case 'post_save':
                $name = LeagueEvents::LEAGUE_POST_SAVE;
                break;
            case 'pre_delete':
                $name = LeagueEvents::LEAGUE_PRE_DELETE;
                break;
            case 'post_delete':
                $name = LeagueEvents::LEAGUE_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\LeagueEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);

            return $event;
        }

        return null;
    }
}
