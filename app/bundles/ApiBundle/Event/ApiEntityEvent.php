<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

final class ApiEntityEvent extends CommonEvent
{
    /**
     * @param object $entity
     */
    public function __construct(
        protected $entity,
        private readonly array $entityRequestParameters,
        private readonly Request $request,
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
