<?php

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

class ApiEntityEvent extends CommonEvent
{
    /**
     * @var array
     */
    protected $entityRequestParameters;

    /**
     * @param object $entity
     */
    public function __construct(protected $entity, array $entityRequestParameters, private Request $request)
    {
        $this->entityRequestParameters = $entityRequestParameters;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return array
     */
    public function getEntityRequestParameters()
    {
        return $this->entityRequestParameters;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
