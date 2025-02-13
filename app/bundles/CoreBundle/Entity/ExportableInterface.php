<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

interface ExportableInterface extends UuidInterface
{
    public function getExportKey(): string;
}
