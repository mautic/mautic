<?php

namespace Mautic\PointBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\PointBundle\Entity\TriggerEvent;
use Mautic\PointBundle\Entity\TriggerEventRepository;
use Mautic\PointBundle\Form\Type\TriggerEventType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * @extends CommonFormModel<TriggerEvent>
 */
class TriggerEventModel extends CommonFormModel
{
    /**
     * @return TriggerEventRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(TriggerEvent::class);
    }

    public function getPermissionBase(): string
    {
        return 'point:triggers';
    }

    public function getEntity($id = null): ?TriggerEvent
    {
        if (null === $id) {
            return new TriggerEvent();
        }

        return parent::getEntity($id);
    }

    /**
     * @throws MethodNotAllowedHttpException
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof TriggerEvent) {
            throw new MethodNotAllowedHttpException(['Trigger']);
        }

        if (!empty($action)) {
            $options['action'] = $action;
        }

        return $formFactory->create(TriggerEventType::class, $entity, $options);
    }

    /**
     * Get segments which are dependent on given segment.
     *
     * @param int $segmentId
     */
    public function getReportIdsWithDependenciesOnSegment($segmentId): array
    {
        $filter = [
            'force'  => [
                ['column' => 'e.type', 'expr' => 'eq', 'value'=>'lead.changelists'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $dependents = [];
        foreach ($entities as $entity) {
            $retrFilters = $entity->getProperties();
            foreach ($retrFilters as $eachFilter) {
                if (in_array($segmentId, $eachFilter)) {
                    $dependents[] = $entity->getTrigger()->getId();
                }
            }
        }

        return $dependents;
    }

    /**
     * @return array<int, int>
     */
    public function getPointTriggerIdsWithDependenciesOnEmail(int $emailId): array
    {
        $filter = [
            'force'  => [
                ['column' => 'e.type', 'expr' => 'in', 'value' => ['email.send', 'email.send_to_user']],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $triggerIds = [];
        foreach ($entities as $entity) {
            $properties = $entity->getProperties();
            if (isset($properties['email']) && (int) $properties['email'] === $emailId) {
                $triggerIds[] = $entity->getTrigger()->getId();
            }
            if (isset($properties['useremail']['email']) && (int) $properties['useremail']['email'] === $emailId) {
                $triggerIds[] = $entity->getTrigger()->getId();
            }
        }

        return array_unique($triggerIds);
    }

    /**
     * @return array<int, int>
     */
    public function getPointTriggerIdsWithDependenciesOnTag(string $tagName): array
    {
        $filter = [
            'force'  => [
                ['column' => 'e.type', 'expr' => 'eq', 'value' => 'lead.changetags'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $triggerIds = [];
        foreach ($entities as $entity) {
            $properties = $entity->getProperties();
            foreach ($properties as $property) {
                if (in_array($tagName, $property)) {
                    $triggerIds[] = $entity->getTrigger()->getId();
                }
            }
        }

        return array_unique($triggerIds);
    }
}
