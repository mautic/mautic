<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PointBundle\Entity\Group;
use Mautic\PointBundle\Entity\GroupContactScore;
use Mautic\PointBundle\Entity\GroupRepository;
use Mautic\PointBundle\Event as Events;
use Mautic\PointBundle\Form\Type\GroupType;
use Mautic\PointBundle\PointGroupEvents;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @extends CommonFormModel<Group>
 */
class PointGroupModel extends CommonFormModel
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
                $name = PointGroupEvents::GROUP_PRE_SAVE;
                break;
            case 'post_save':
                $name = PointGroupEvents::GROUP_POST_SAVE;
                break;
            case 'pre_delete':
                $name = PointGroupEvents::GROUP_PRE_DELETE;
                break;
            case 'post_delete':
                $name = PointGroupEvents::GROUP_POST_DELETE;
                break;
            default:
                return null;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new Events\GroupEvent($entity);
            }
            $this->dispatcher->dispatch($event, $name);

            return $event;
        }

        return null;
    }

    public function adjustPoints(Lead $contact, Group $group, int $points, string $operator = Lead::POINTS_ADD): Lead
    {
        $contactScore = $contact->getGroupScore($group);

        if (empty($contactScore)) {
            $contactScore = new GroupContactScore();
            $contactScore->setContact($contact);
            $contactScore->setGroup($group);
            $contactScore->setScore(0);
            $contact->addGroupScore($contactScore);
        }
        $oldScore = $contactScore->getScore();
        $newScore = $oldScore;

        switch ($operator) {
            case Lead::POINTS_ADD:
                $newScore += $points;
                break;
            case Lead::POINTS_SUBTRACT:
                $newScore -= $points;
                break;
            case Lead::POINTS_MULTIPLY:
                $newScore *= $points;
                break;
            case Lead::POINTS_DIVIDE:
                $newScore /= $points;
                break;
            default:
                throw new \UnexpectedValueException('Invalid operator');
        }
        $contactScore->setScore($newScore);
        $this->em->persist($contactScore);
        $this->em->flush();

        $scoreChangeEvent = new Events\GroupScoreChangeEvent($contactScore, $oldScore, $newScore);
        $this->dispatcher->dispatch($scoreChangeEvent, PointGroupEvents::SCORE_CHANGE);

        return $contact;
    }
}
