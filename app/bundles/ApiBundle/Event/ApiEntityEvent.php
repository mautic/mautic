<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

abstract class ApiEntityEvent extends CommonEvent
{
    public function __construct(
        object $entity,
        private readonly array $entityRequestParameters,
        private readonly Request $request,
    ) {
        $this->entity = $entity;
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
