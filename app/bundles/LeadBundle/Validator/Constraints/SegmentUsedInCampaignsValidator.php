<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator as InternalValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SegmentUsedInCampaignsValidator extends ConstraintValidator
{
    public function __construct(private readonly InternalValidator $internalValidator)
    {
    }

    public function validate(mixed $segment, Constraint $constraint): void
    {
        /** @var LeadList $segment */
        if ($segment->getIsPublished()) {
            return;
        }

        if ($this->internalValidator->validate($segment)) {
            $this->context->buildViolation($this->internalValidator->getErrorMessage())
                ->atPath('isPublished')
                ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                ->addViolation();
        }
    }
}
