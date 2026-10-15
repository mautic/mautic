<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Provider;

interface VersionProviderInterface
{
    public function getVersion(): string;

    public function isMariaDb(): bool;

    public function isMySql(): bool;

    public function isPostgreSql(): bool;
}
