<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Entity\CompanySegmentRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueCompanySegmentAliasValidator extends ConstraintValidator
{
    public function __construct(public CompanySegmentRepository $companySegmentRepository, public UserHelper $userHelper)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof CompanySegment) {
            throw new UnexpectedValueException($value, CompanySegment::class);
        }

        if (!$constraint instanceof UniqueCompanySegmentAlias) {
            throw new UnexpectedTypeException($constraint, UniqueCompanySegmentAlias::class);
        }

        $field = $constraint->field;

        $alias = $value->getAlias();
        if (null === $alias || '' === $alias) {
            return;
        }

        $segments = $this->companySegmentRepository->getSegments(
            $this->userHelper->getUser(),
            $alias,
            $value->getId()
        );

        if (0 === count($segments)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->atPath($field)
            ->addViolation();
    }
}
