<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

interface ResourceInstallerInterface
{
    public function isInstalled(string $packageName): bool;

    /**
     * @return array{success: bool, summary: array<mixed>, errors: array<string>}
     */
    public function install(string $packageName, int $userId): array;

    public function uninstall(string $packageName): void;
}
