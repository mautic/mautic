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
    public function initialize(FilterControllerEvent $event)
    {
        parent::initialize($event);
        $this->fieldObject     = $this->request->get('object');
        $this->model           = $this->getModel('lead.field');
        $this->entityClass     = LeadField::class;
        $this->entityNameOne   = 'field';
        $this->entityNameMulti = 'fields';
        $this->permissionBase  = 'lead:fields';
        $this->routeParams     = ['object' => $this->fieldObject];

        if ($this->fieldObject === 'contact') {
            $this->fieldObject = 'lead';
        }
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
}
