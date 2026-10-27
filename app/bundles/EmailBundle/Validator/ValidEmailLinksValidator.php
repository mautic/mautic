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
            return UrlHelper::isValidUrl($url);
        }

        return in_array($scheme, ['mailto', 'tel', 'sms'], true);
    }
}
