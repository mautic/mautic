<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailValidationEvent;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class EmailValidator.
 */
class EmailValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * EmailValidator constructor.
     *
     * @param TranslatorInterface      $translator
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(TranslatorInterface $translator, EventDispatcherInterface $dispatcher)
    {
        $this->translator = $translator;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Validate that an email is the correct format, doesn't have invalid characters, a MX record is associated with the domain, and
     * leverage integrations to validate.
     *
     * @param $address
     * @param bool $doDnsCheck
     *
     * @throws InvalidEmailException
     */
    public function validate($address, $doDnsCheck = false)
    {
        if (!$this->isValidFormat($address)) {
            throw new InvalidEmailException(
                $address,
                $this->translator->trans(
                    'mautic.email.address.invalid_format',
                    [
                        '%email%' => $address ?: '?',
                    ]
                )
            );
        }

        if ($this->hasValidCharacters($address)) {
            throw new InvalidEmailException(
                $address,
                $this->translator->trans('mautic.email.address.invalid_characters', ['%email%' => $address])
            );
        }

        if ($doDnsCheck && !$this->hasValidDomain($address)) {
            throw new InvalidEmailException(
                $address,
                $this->translator->trans('mautic.email.address.invalid_domain', ['%email%' => $address])
            );
        }

        $this->doPluginValidation($address);
    }

    /**
     * Validates that email is in an acceptable format.
     *
     * @param $address
     *
     * @returns bool
     */
    public function isValidFormat($address)
    {
        return !empty($address) && filter_var($address, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Validates that email does not have invalid characters.
     *
     * @param $address
     *
     * @returns bool
     */
    public function hasValidCharacters($address)
    {
        $invalidChar = strpbrk($address, '\'^&*%');

        return $invalidChar ? substr($invalidChar, 0, 1) : $invalidChar;
    }

    /**
     * Validates if the domain of an email.
     *
     * @param $address
     *
     * @returns bool
     */
    public function hasValidDomain($address)
    {
        list($user, $domain) = explode('@', $address);

        return checkdnsrr($domain, 'MX');
    }

    /**
     * Validate using 3rd party integrations.
     *
     * @param $address
     *
     * @throws InvalidEmailException
     */
    public function doPluginValidation($address)
    {
        $event = $this->dispatcher->dispatch(
            EmailEvents::ON_EMAIL_VALIDATION,
            new EmailValidationEvent($address)
        );

        if (!$event->isValid()) {
            throw new InvalidEmailException(
                $address,
                $event->getInvalidReason()
            );
        }
    }
}
