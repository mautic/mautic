<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class LeadListAccessValidator extends ConstraintValidator
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    public function __construct(ListModel $segmentModel)
    {
        $this->segmentModel = $segmentModel;
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint)
    {
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
        } else {
            $this->context->addViolation($constraint->message);
        }
    }
}
