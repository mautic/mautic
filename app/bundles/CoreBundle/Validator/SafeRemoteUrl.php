<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;

final class SafeRemoteUrl extends Constraint
{
    public string $message = 'mautic.core.remote_url_not_allowed';
}
