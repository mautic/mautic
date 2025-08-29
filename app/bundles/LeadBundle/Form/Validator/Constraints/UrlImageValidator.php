<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UrlImageValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$value || !is_string($value)) {
            return;
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg'];
        $pathInfo          = pathinfo(parse_url($value, PHP_URL_PATH));
        $extension         = strtolower($pathInfo['extension'] ?? '');

        // Remove any query parameters from the URL
        $parts = parse_url($value);

        if (array_key_exists('query', $parts) && is_string($parts['query']) && '' !== $parts['query']) {
            $this->context->buildViolation('mautic.lead.field.companylogourl.invalid')
                ->addViolation();

            return;
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            $this->context->buildViolation('mautic.lead.field.companylogourl.invalid')
                ->addViolation();

            return;
        }

        try {
            $headers = @get_headers($value, true);
        } catch (\Throwable) {
            $this->context->buildViolation('mautic.lead.field.companylogourl.invalid')
                ->addViolation();

            return;
        }
        if ($headers && isset($headers['Content-Type'])) {
            $contentType = is_array($headers['Content-Type']) ? $headers['Content-Type'][0] : $headers['Content-Type'];
            if (!preg_match('#^image/(jpeg|png|svg\+xml)$#', $contentType)) {
                $this->context->buildViolation('mautic.lead.field.companylogourl.invalid')
                    ->addViolation();

                return;
            }
        }

        if ('' !== $value && (!$headers || !array_key_exists('Content-Type', $headers))) {
            $this->context->buildViolation('mautic.lead.field.companylogourl.invalid')
                ->addViolation();
        }
    }
}
