<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

interface EmailConfigInterface
{
    public function isDraftEnabled(): bool;
}
