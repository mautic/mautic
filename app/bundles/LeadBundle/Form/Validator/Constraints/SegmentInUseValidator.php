<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SegmentInUseValidator extends ConstraintValidator
{
    public function __construct(
        private ListModel $listModel,
    ) {
    }

    /**
     * @param LeadList $leadList
     */
    public function validate($leadList, Constraint $constraint): void
    {
        if (!$constraint instanceof SegmentInUse) {
            throw new UnexpectedTypeException($constraint, SegmentInUse::class);
        }

        if (!$leadList->getId() || $leadList->getIsPublished()) {
            return;
        }

        $lists = $this->listModel->getSegmentsWithDependenciesOnSegment($leadList->getId(), 'name');

        if (count($lists)) {
            $this->context->buildViolation($constraint->message)
                ->setCode((string) Response::HTTP_UNPROCESSABLE_ENTITY)
                ->setParameter('%segments%', implode(',', $lists))
                ->addViolation();
        }
    }
}
