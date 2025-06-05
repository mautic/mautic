<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class SlotNameTypeValidator extends ConstraintValidator
{
    public function __construct(private DynamicContentModel $dynamicContentModel)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SlotNameType) {
            throw new UnexpectedTypeException($constraint, SlotNameType::class);
        }

        if (!$value instanceof DynamicContent) {
            return;
        }

        $slotName = $value->getSlotName();
        if (empty($slotName) || $value->getIsCampaignBased()) {
            return;
        }

        $existingContents = $this->dynamicContentModel->
            checkEntityBySlotName($slotName, $value->getType(), '!=', $value->getId());
        if ($existingContents) {
            $this->context->buildViolation($constraint->message)
                ->atPath('type')
                ->addViolation();
        }
    }
}
