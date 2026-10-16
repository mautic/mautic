<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DwcTokenValidator extends ConstraintValidator
{
    private const REGEX_WITH_MANDATORY_DEFAULT_CONTENT    = '/\{dwc=([^\{\}=]+)\}(.+?)\{\/dwc\}/s';

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DwcToken) {
            throw new UnexpectedTypeException($constraint, DwcToken::class);
        }

        if (empty($value) || !is_string($value)) {
            return;
        }

        preg_match_all(DynamicContentHelper::DYNAMIC_WEB_CONTENT_REGEX, $value, $openTags);
        preg_match_all(self::REGEX_WITH_MANDATORY_DEFAULT_CONTENT, $value, $pairs);
        if (count($openTags[0]) !== count($pairs[0])) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
