<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class LeadListAccessValidator extends ConstraintValidator
{
    public function __construct(
        private ListModel $segmentModel,
    ) {
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof LeadListAccess) {
            throw new UnexpectedTypeException($constraint, LeadListAccess::class);
        }

        if (count($value)) {
            $lists = $this->segmentModel->getUserLists();
            foreach ($value as $l) {
                if (!isset($lists[$l->getId()])) {
                    $this->context->addViolation(
                        $constraint->message,
                        ['%string%' => $l->getName()]
                    );
                    break;
                }
            }
        } elseif (!$constraint->allowEmpty) {
            $this->context->addViolation($constraint->message);
        }
    }
}
