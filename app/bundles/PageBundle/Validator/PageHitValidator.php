<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class PageHitValidator extends ConstraintValidator
{
    public function __construct(private readonly CoreParametersHelper $coreParametersHelper)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$this->coreParametersHelper->get('validate_page_hit_required_data')) {
            return;
        }

        if (!$value instanceof Hit) {
            throw new UnexpectedTypeException($value, Hit::class);
        }

        // We are not validating 404 request as 404 page hit can be persisted with null values
        if (404 === $value->getCode()) {
            return;
        }

        if ($value->getPage() || $value->getRedirect()) {
            return;
        }

        if (!$value->getUrl() || !$value->getUrlTitle()) {
            $this->context
                ->buildViolation('page_id / redirect_id / page_url & page_title should not be empty')
                ->addViolation();
        }
    }
}
