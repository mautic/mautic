<?php

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailValidationEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailValidator
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $dispatcher
    ) {
    }

    /**
     * Validate that an email is the correct format, doesn't have invalid characters, a MX record is associated with the domain, and
     * leverage integrations to validate.
     *
     * @param bool $doDnsCheck
     *
     * @throws InvalidEmailException
     */
    public function validate($address, $doDnsCheck = false): void
    {
        if (!$this->isValidFormat($address)) {
            throw new InvalidEmailException($address, $this->translator->trans('mautic.email.address.invalid_format', ['%email%' => $address ?: '?']));
        }

        if ($this->hasValidCharacters($address)) {
            throw new InvalidEmailException($address, $this->translator->trans('mautic.email.address.invalid_characters', ['%email%' => $address]));
        }

        if ($doDnsCheck && !$this->hasValidDomain($address)) {
            throw new InvalidEmailException($address, $this->translator->trans('mautic.email.address.invalid_domain', ['%email%' => $address]));
        }

        $this->doPluginValidation($address);
    }

    /**
     * Validates that email is in an acceptable format.
     *
     * @returns bool
     */
    public function isValidFormat($address): bool
    {
        return !empty($address) && filter_var($address, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates that email does not have invalid characters.
     *
     * @returns bool
     */
    public function hasValidCharacters($address)
    {
        $invalidChar = strpbrk($address, '^&*%');

        return $invalidChar ? substr($invalidChar, 0, 1) : $invalidChar;
    }

    /**
     * Validates if the domain of an email.
     *
     * @returns bool
     */
    public function hasValidDomain($address): bool
    {
        [$user, $domain] = explode('@', $address);

        return checkdnsrr($domain, 'MX');
    }

    /**
     * Validate using 3rd party integrations.
     *
     * @throws InvalidEmailException
     */
    public function doPluginValidation($address): void
    {
        $event = $this->dispatcher->dispatch(
            new EmailValidationEvent($address),
            EmailEvents::ON_EMAIL_VALIDATION
        );

        if (!$event->isValid()) {
            throw new InvalidEmailException($address, $event->getInvalidReason());
        }
    }
}
