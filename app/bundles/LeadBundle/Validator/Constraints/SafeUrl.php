<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class SafeUrl extends Constraint
{
    public string $dataProtocolMessage = 'mautic.lead.dataProtocolMessage';
}
