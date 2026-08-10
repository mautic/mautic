<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class InfiniteLoop extends Constraint
{
}
