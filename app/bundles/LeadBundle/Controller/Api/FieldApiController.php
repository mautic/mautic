<?php

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Field\Exception\AbortColumnCreateException;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * @extends CommonApiController<LeadField>
 */
class FieldApiController extends CommonApiController
{
    /**
     * Can have value of 'contact' or 'company'.
     *
     * @var string
     */
    protected $fieldObject;

    /**
     * @var FieldModel|null
     */
    protected $model = null;

    public function initialize(ControllerEvent $event)
    {
        $fieldModel = $this->getModel('lead.field');

        if (!$fieldModel instanceof FieldModel) {
            throw new \RuntimeException('Wrong model given.');
        }

        $this->model           = $fieldModel;
        $this->fieldObject     = $this->request->get('object');
        $this->entityClass     = LeadField::class;
        $this->entityNameOne   = 'field';
        $this->entityNameMulti = 'fields';
        $this->routeParams     = ['object' => $this->fieldObject];

        if ('contact' === $this->fieldObject) {
            $this->fieldObject = 'lead';
        }

        $repo                = $this->model->getRepository();
        $tableAlias          = $repo->getTableAlias();
        $this->listFilters[] = [
            'column' => $tableAlias.'.object',
            'expr'   => 'eq',
            'value'  => $this->fieldObject,
        ];

        parent::initialize($event);
    }

    protected function saveEntity($entity, int $statusCode): int
    {
        try {
            return parent::saveEntity($entity, $statusCode);
        } catch (AbortColumnCreateException $exception) {
            // Field has been queued
            return Response::HTTP_ACCEPTED;
        }
    }

    /**
     * Sanitizes and returns an array of where statements from the request.
     *
     * @return array
     */
    protected function getWhereFromRequest()
    {
        $where = parent::getWhereFromRequest();

        $where[] = [
            'col'  => 'object',
            'expr' => 'eq',
            'val'  => $this->fieldObject,
        ];

        return $where;
    }

    /**
     * @param $parameters
     * @param $entity
     * @param $action
     *
     * @return mixed|void
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        $parameters['object'] = $this->fieldObject;
        // Workaround for mispelled isUniqueIdentifer.
        if (isset($parameters['isUniqueIdentifier'])) {
            $parameters['isUniqueIdentifer'] = $parameters['isUniqueIdentifier'];
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param LeadField &$entity
     * @param           $parameters
     * @param           $form
     * @param string    $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($parameters['properties'])) {
            $result = $this->model->setFieldProperties($entity, $parameters['properties']);

            if (true !== $result) {
                return $this->returnError($this->get('translator')->trans($result, [], 'validators'), Response::HTTP_BAD_REQUEST);
            }
        }
    }
}
