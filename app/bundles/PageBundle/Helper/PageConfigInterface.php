<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Helper;

interface PageConfigInterface
{
    public function isDraftEnabled(): bool;
}
