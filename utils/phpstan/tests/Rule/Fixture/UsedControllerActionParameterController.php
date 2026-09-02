<?php

declare(strict_types=1);

namespace Utils\PHPStan\Tests\Rule\Fixture;

final class UsedControllerActionParameterController
{
    public function indexAction(string $request, string $idHash): string
    {
        return $request.$idHash;
    }

    // a scope-reading call makes the parameters undecidable, so the action is skipped
    public function listAction(string $model): string
    {
        return implode(',', compact('model'));
    }

    // a by-reference parameter is written for the caller, not read here
    public function exportAction(array &$rows): void
    {
        $rows[] = 'row';
    }

    // not an action, and not a controller concern
    public function getModelName(string $unused): string
    {
        return 'name';
    }
}
