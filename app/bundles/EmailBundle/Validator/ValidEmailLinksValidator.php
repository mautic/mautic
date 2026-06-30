<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use GuzzleHttp\Psr7\Uri;
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

        $this->validateHtml($value->getCustomHtml(), 'customHtml', $constraint);

        foreach ($this->findHtmlStrings($value->getContent()) as $html) {
            $this->validateHtml($html, 'content', $constraint);
        }
    }

    private function validateHtml(mixed $html, string $path, ValidEmailLinks $constraint): void
    {
        if (!is_string($html) || '' === trim($html)) {
            return;
        }

        $crawler = new Crawler($html);

        foreach ($crawler->filter('a[href]') as $link) {
            if (!$link instanceof \DOMElement) {
                continue;
            }

            $url = html_entity_decode($link->getAttribute('href'), ENT_QUOTES | ENT_HTML5);

            if ($this->isValidUrl($url)) {
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

    private function isValidUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (false === $parts) {
            return false;
        }

        try {
            Uri::fromParts($parts);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
