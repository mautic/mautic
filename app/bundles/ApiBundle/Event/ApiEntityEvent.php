<?php

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

class ApiEntityEvent extends CommonEvent
{
    /**
     * @var object
     */
    protected $entity;

    /**
     * @var array
     */
    protected $entityRequestParameters;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param object $entity
     */
    public function __construct($entity, array $entityRequestParameters, Request $request)
    {
        $this->entity                  = $entity;
        $this->entityRequestParameters = $entityRequestParameters;
        $this->request                 = $request;
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
