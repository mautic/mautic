<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TextOnlyDynamicContentValidator extends ConstraintValidator
{
    public function __construct(private DynamicContentModel $dynamicContentModel)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$constraint instanceof TextOnlyDynamicContent) {
            throw new UnexpectedTypeException($constraint, TextOnlyDynamicContent::class);
        }

        // Pattern to match DWC tokens in the format {dwc=slotname}
        preg_match_all('/{dwc=([^}]*)}/', $value, $matches);

        foreach ($matches[1] as $slotName) {
            // Retrieve DWC item by slot name
            $dwcItem = $this->dynamicContentModel->checkEntityBySlotName($slotName, TypeList::HTML);

            // Perform the validation against the type
            if ($dwcItem) {
                $this->context->buildViolation(
                    $constraint->message, ['%slotName%' => $slotName])
                    ->addViolation();
            }
        }
    }
}
