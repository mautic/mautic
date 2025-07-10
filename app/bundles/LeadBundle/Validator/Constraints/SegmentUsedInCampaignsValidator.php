<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\CoreBundle\Exception\RecordNotUnpublishedException;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator as InternalValidator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class SegmentUsedInCampaignsValidator extends ConstraintValidator
{
    public function __construct(private InternalValidator $internalValidator)
    {
    }

    public function validate(mixed $segment, Constraint $constraint): void
    {
        try {
            /** @var LeadList $segment */
            if ($segment->getIsPublished()) {
                return;
            }

            $this->internalValidator->validate($segment);
        } catch (RecordNotUnpublishedException $exception) {
            $this->context->buildViolation($exception->getMessage())
                ->atPath('isPublished')
                ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                ->addViolation();
        }
    }
}
