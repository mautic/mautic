<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Validator;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SafeRemoteUrlValidator extends ConstraintValidator
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SafeRemoteUrl) {
            throw new UnexpectedTypeException($constraint, SafeRemoteUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!$this->isSafeUrl((string) $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function isSafeUrl(string $url): bool
    {
        if (!$this->coreParametersHelper->get('validate_remote_domains')) {
            return true;
        }

        if (!$domain = $this->parseDomain($url)) {
            return false;
        }

        $allowedDomains = array_map('strtolower', $this->coreParametersHelper->get('allowed_remote_domains'));

        if ($siteDomain = $this->parseDomain((string) $this->coreParametersHelper->get('site_url'))) {
            $allowedDomains[] = $siteDomain;
        }

        return in_array($domain, $allowedDomains, true);
    }

    private function parseDomain(string $url): string
    {
        return strtolower(parse_url($url, PHP_URL_HOST) ?: '');
    }
}
