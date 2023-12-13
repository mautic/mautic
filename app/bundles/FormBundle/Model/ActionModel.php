<?php

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Form\Type\ActionType;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * @extends CommonFormModel<Action>
 */
class ActionModel extends CommonFormModel
{
    /**
     * @return \Mautic\FormBundle\Entity\ActionRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository(\Mautic\FormBundle\Entity\Action::class);
    }

    public function getPermissionBase(): string
    {
        return 'form:forms';
    }

    public function getEntity($id = null): ?Action
    {
        if (null === $id) {
            return new Action();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object $entity
     * @param array  $options
     */
    public function createForm($entity, FormFactoryInterface $formFactory, $action = null, $options = []): \Symfony\Component\Form\FormInterface
    {
        if (!$entity instanceof Action) {
            throw new \InvalidArgumentException('Entity must be of class Action');
        }

        if ($action) {
            $options['action'] = $action;
        }

        if (empty($options['formId']) && null !== $entity->getForm()) {
            $options['formId'] = $entity->getForm()->getId();
        }

        return $formFactory->create(ActionType::class, $entity->convertToArray(), $options);
    }

    /**
     * Get segments which are dependent on given segment.
     *
     * @param int $segmentId
     */
    public function getFormsIdsWithDependenciesOnSegment($segmentId): array
    {
        $filter = [
            'force'  => [
                ['column' => 'e.type', 'expr' => 'LIKE', 'value'=>'lead.changelist'],
            ],
        ];
        $entities = $this->getEntities(
            [
                'filter'     => $filter,
            ]
        );
        $dependents = [];
        foreach ($entities as $entity) {
            $properties = $entity->getProperties();
            foreach ($properties as $property) {
                if (in_array($segmentId, $property)) {
                    $dependents[] = $entity->getForm()->getId();
                }
            }
        }

        return $dependents;
    }
}
