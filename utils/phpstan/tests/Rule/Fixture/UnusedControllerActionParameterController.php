<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class UnusedControllerActionParameterController
{
    public function unsubscribeAction(
        string $request,
        string $leadModel,
        ?string $secretHash = null,
    ): string {
        return $request.$secretHash;
    }

    public function __invoke(string $id, string $unusedName): string
    {
        return $id;
    }
}
