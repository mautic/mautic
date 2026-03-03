<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Validator\Constraints;

use Mautic\CampaignBundle\Entity\Campaign;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class NoOrphanEventsValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Campaign) {
            return;
        }

        if ($value->hasOrphanEvents()) {
            $this->context->buildViolation('mautic.campaign.form.events.orphan')
                ->addViolation();
        }
    }
}
