<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

final class DeleteEntitiesDependencyException extends \Exception
{
    /**
     * @param object[] $deletedEntities
     * @param object[] $unableToDeleteEntities
     */
    public function __construct(
        private array $deletedEntities,
        private array $unableToDeleteEntities,
        string $message = '',
        int $code = Response::HTTP_CONFLICT,
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return object[]
     */
    public function getDeletedEntities(): array
    {
        return $this->deletedEntities;
    }

    /**
     * @return object[]
     */
    public function getUnableToDeleteEntities(): array
    {
        return $this->unableToDeleteEntities;
    }
}
