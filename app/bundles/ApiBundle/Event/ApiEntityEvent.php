<?php

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

class ApiEntityEvent extends CommonEvent
{
    /**
     * @param object $entity
     */
    public function __construct(
        protected $entity,
        protected array $entityRequestParameters,
        private Request $request,
    ) {
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function getEntityRequestParameters(): array
    {
        return $this->entityRequestParameters;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
