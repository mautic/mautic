<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

final class UniqueUserAliasValidator extends ConstraintValidator
{
    public function __construct(
        public LeadListRepository $segmentRepository,
        public UserHelper $userHelper,
    ) {
    }

    public function validate(mixed $list, Constraint $constraint): void
    {
        $field = $constraint->field;

        if (empty($field)) {
            throw new ConstraintDefinitionException('A field has to be specified.');
        }

        if ($list->getAlias()) {
            $lists = $this->segmentRepository->getLists(
                $this->userHelper->getUser(),
                $list->getAlias(),
                $list->getId()
            );

            if (count($lists)) {
                $this->context->buildViolation($constraint->message)
                    ->atPath($field)
                    ->setParameter('%alias%', $list->getAlias())
                    ->addViolation();
            }
        }
    }
}
