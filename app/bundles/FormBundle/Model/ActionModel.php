<?php

namespace Mautic\FormBundle\Model;

use Mautic\CoreBundle\Model\FormModel as CommonFormModel;
use Mautic\FormBundle\Entity\Action;
use Mautic\FormBundle\Form\Type\ActionType;

/**
 * Class ActionModel.
 */
class ActionModel extends CommonFormModel
{
    /**
     * {@inheritdoc}
     *
     * @return \Mautic\FormBundle\Entity\ActionRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticFormBundle:Action');
    }

    /**
     * {@inheritdoc}
     */
    public function getPermissionBase()
    {
        return 'form:forms';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($id = null)
    {
        if (null === $id) {
            return new Action();
        }

        return parent::getEntity($id);
    }

    /**
     * @param object                              $entity
     * @param \Symfony\Component\Form\FormFactory $formFactory
     * @param null                                $action
     * @param array                               $options
     */
    public function createForm($entity, $formFactory, $action = null, $options = [])
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
     *
     * @return array
     */
    public function getFormsIdsWithDependenciesOnSegment($segmentId)
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
