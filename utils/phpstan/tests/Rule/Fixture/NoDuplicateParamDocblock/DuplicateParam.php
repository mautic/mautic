<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture\NoDuplicateParamDocblock;

class DuplicateParam
{
    /**
     * @param object      $entity
     * @param Campaign    $entity
     * @param string|null $action
     */
    public function createForm($entity, $action = null): void
    {
    }

    /**
     * @param object $entity
     * @param string $action
     */
    public function skipSingleParams($entity, $action): void
    {
    }

    public function skipNoDocblock($entity): void
    {
    }
}
