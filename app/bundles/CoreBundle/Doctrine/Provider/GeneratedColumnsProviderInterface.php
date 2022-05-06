<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Provider;

use Mautic\CoreBundle\Doctrine\GeneratedColumn\GeneratedColumns;

interface GeneratedColumnsProviderInterface
{
    public function getGeneratedColumns(): GeneratedColumns;

    public function generatedColumnsAreSupported(): bool;

    public function getMinimalSupportedVersion(): string;
}
