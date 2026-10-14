<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * @template TEntity of object
 */
abstract class ApiEntityEvent extends CommonEvent
{
    /**
     * @param TEntity $entity
     */
    public function __construct(
        object $entity,
        private readonly array $entityRequestParameters,
        private readonly Request $request,
    ) {
        $this->entity = $entity;
    }

    /**
     * @return TEntity
     */
    public function getEntity(): object
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
