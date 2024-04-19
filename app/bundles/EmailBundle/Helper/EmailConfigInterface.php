<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\Entity\Email;

interface EmailConfigInterface
{
    public function isDraftEnabled(): bool;
}
