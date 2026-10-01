<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidEmailLinksValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidEmailLinks) {
            throw new UnexpectedTypeException($constraint, ValidEmailLinks::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Email) {
            throw new UnexpectedValueException($value, Email::class);
        }

        $customHtml = $value->getCustomHtml();
        if (null !== $customHtml && '' !== trim($customHtml)) {
            $this->validateHtml($customHtml, 'customHtml', $constraint);

            return;
        }

        foreach ($this->findHtmlStrings($value->getContent()) as $html) {
            $this->validateHtml($html, 'content', $constraint);
        }
    }

    private function validateHtml(?string $html, string $path, ValidEmailLinks $constraint): void
    {
        if (null === $html || '' === trim($html)) {
            return;
        }

        $crawler = new Crawler($html);

        foreach ($crawler->filter('a[href]') as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }

            $url = html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5);

            if ($this->isMauticToken($url) || $this->hasValidScheme($url)) {
                continue;
            }

            $this->context->buildViolation($constraint->message)
                ->setParameter('%url%', $url)
                ->atPath($path)
                ->addViolation();
        }
    }

    /**
     * @return iterable<string>
     */
    private function findHtmlStrings(mixed $content): iterable
    {
        if (is_string($content)) {
            yield $content;

            return;
        }

        if (!is_array($content)) {
            return;
        }

        foreach ($content as $value) {
            yield from $this->findHtmlStrings($value);
        }
    }

    private function isMauticToken(string $url): bool
    {
        return 1 === preg_match('/^\{[^{}]+\}$/', $url);
    }

    private function hasValidScheme(string $url): bool
    {
        if (str_starts_with($url, '#')) {
            return true;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (in_array($scheme, ['http', 'https', 'ftp', 'ftps'], true)) {
            if (UrlHelper::isValidUrl($url)) {
                return true;
            }

            // [ee-patch-2026-09-09 v2] builder round-trips can hand the
            // validator scheme-carrying hrefs with raw spaces / unencoded
            // > / parens (see upstream issue below); probe the same
            // percent-encoded form before rejecting.
            $probe = $this->encodeUrlProbeChars($url);

            return UrlHelper::isValidUrl($probe);
        }

        if (in_array($scheme, ['mailto', 'tel', 'sms'], true)) {
            return true;
        }

        // [ee-patch-2026-09-09 v2] normalize-then-validate instead of reject:
        // path-relative and query-only anchors are template links (valid
        // without a host), and host-prefixed scheme-less forms are validated
        // after https:// scheme-fill plus percent-encoding, so Mautic's own
        // click-tag pattern (?tags=Tag Name -> Stage) passes when the exact
        // encoded form already passes. Still fails unparseable hrefs.
        if (str_starts_with($url, '/') || str_starts_with($url, '?')) {
            return true;
        }

        $candidate = $this->schemelessCandidateUrl($url);
        if (null === $candidate) {
            return false;
        }

        return UrlHelper::isValidUrl('https://'.$this->encodeUrlProbeChars($candidate));
    }

    /**
     * Percent-encodes characters a URL validator (filter_var) rejects —
     * raw spaces and angle brackets — while keeping the scheme and existing
     * percent-sequences untouched, so the probe form is click-equivalent.
     */
    private function encodeUrlProbeChars(string $raw): string
    {
        return (string) preg_replace_callback(
            '/%[0-9A-Fa-f]{2}|[^A-Za-z0-9_.~!$&()*+,;=:@\/?%#\-]/',
            function (array $m): string {
                return '%' === $m[0][0] ? $m[0] : rawurlencode($m[0]);
            },
            $raw
        );
    }

    /**
     * Returns $url when it is a host-prefixed scheme-less form that carries a
     * path, query or fragment (safe to probe with an https:// prefix), else
     * null. Restricting the shape keeps mailto:-like and pseudo-scheme text
     * out of the probe.
     */
    private function schemelessCandidateUrl(string $url): ?string
    {
        if ('' === $url
            || str_starts_with($url, '/')
            || str_starts_with($url, '?')
            || str_starts_with($url, '#')
        ) {
            return null;
        }

        if (1 !== preg_match('/^[A-Za-z0-9][A-Za-z0-9.\-]*(\.[A-Za-z][A-Za-z0-9\-]*)+[\/?#]/D', $url)) {
            return null;
        }

        return $url;
    }
}
