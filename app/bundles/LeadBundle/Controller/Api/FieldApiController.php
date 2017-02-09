<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FieldApiController.
 */
class FieldApiController extends CommonApiController
{
    protected $fieldObject;

    public function initialize(FilterControllerEvent $event)
    {
        $this->fieldObject     = $this->request->get('object');
        $this->model           = $this->getModel('lead.field');
        $this->entityClass     = LeadField::class;
        $this->entityNameOne   = 'field';
        $this->entityNameMulti = 'fields';
        $this->routeParams     = ['object' => $this->fieldObject];

        if ($this->fieldObject === 'contact') {
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

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead &$entity
     * @param                                $parameters
     * @param                                $form
     * @param string                         $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($parameters['properties'])) {
            $result = $this->model->setFieldProperties($entity, $parameters['properties']);

            if ($result !== true) {
                return $this->returnError($this->get('translator')->trans($result, [], 'validators'), Codes::HTTP_BAD_REQUEST);
            }
        }
    }
}
