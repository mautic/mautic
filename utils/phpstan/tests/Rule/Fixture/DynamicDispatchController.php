<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class DynamicDispatchController
{
    public function executeAction(string $request, string $objectAction, string $objectId): string
    {
        return $this->{"{$objectAction}Action"}($request, $objectId);
    }

    // $request is unused here, but the dynamic dispatch above hands it over positionally, so it must stay
    public function deleteAction(string $request, string $objectId): string
    {
        return $objectId;
    }
}
