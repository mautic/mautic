<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Helper\DTO\AddressDTO;
use Mautic\EmailBundle\Helper\Exception\OwnerNotFoundException;
use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;
use Mautic\LeadBundle\Entity\LeadRepository;

class FromEmailHelper
{
    /**
     * @var array<int,mixed[]>
     */
    private array $owners = [];

    private ?AddressDTO $defaultFrom = null;

    /**
     * @var mixed[]|null
     */
    private ?array $lastOwner = null;

    public function __construct(private CoreParametersHelper $coreParametersHelper, private LeadRepository $leadRepository)
    {
    }

    public function setDefaultFrom(AddressDTO $from): void
    {
        $this->defaultFrom = $from;
    }

    /**
     * @param mixed[] $contact
     */
    public function getFromAddressConsideringOwner(AddressDTO $address, ?array $contact = null, ?Email $email = null): AddressDTO
    {
        // Reset last owner
        $this->lastOwner = null;

        // Check for token
        if ($address->isEmailTokenized() || $address->isNameTokenized()) {
            return $this->getEmailFromToken($address, $contact, true, $email);
        }

        if (!$contact) {
            return $address;
        }

        try {
            return $this->getFromEmailAsOwner($contact, $email);
        } catch (OwnerNotFoundException) {
            return $this->getFrom($email);
        }
    }

    /**
     * @param mixed[] $contact
     */
    public function getFromAddressDto(AddressDTO $address, ?array $contact = null, ?Email $email = null): AddressDTO
    {
        // Reset last owner
        $this->lastOwner = null;

        // Check for token
        if ($address->isEmailTokenized() || $address->isNameTokenized()) {
            return $this->getEmailFromToken($address, $contact, false, $email);
        }

        return $address;
    }

    /**
     * @return mixed[]
     *
     * @throws OwnerNotFoundException
     */
    public function getContactOwner(int $userId, ?Email $email = null): array
    {
        // Reset last owner
        $this->lastOwner = null;

        if ($email) {
            if (!$email->getUseOwnerAsMailer()) {
                throw new OwnerNotFoundException("mailer_is_owner is not enabled for this email ({$email->getId()})");
            }
        } elseif (!$this->coreParametersHelper->get('mailer_is_owner')) {
            throw new OwnerNotFoundException('mailer_is_owner is not enabled in global configuration');
        }

        if (isset($this->owners[$userId])) {
            return $this->lastOwner = $this->owners[$userId];
        }

        if ($owner = $this->leadRepository->getLeadOwner($userId)) {
            $this->owners[$userId] = $this->lastOwner = $owner;

            return $owner;
        }

        throw new OwnerNotFoundException();
    }

    public function hasSignature(): bool
    {
        return (bool) $this->lastOwner;
    }

    public function getSignature(): string
    {
        if (!$this->lastOwner) {
            return '';
        }

        return $this->replaceSignatureTokens($this->lastOwner);
    }

    /**
     * @param mixed[] $owner
     */
    private function replaceSignatureTokens(array $owner): string
    {
        $signature = nl2br($owner['signature'] ?? '');
        $signature = str_replace('|FROM_NAME|', $owner['first_name'].' '.$owner['last_name'], $signature);

        foreach ($owner as $key => $value) {
            $token     = sprintf('|USER_%s|', strtoupper((string) $key));
            $signature = str_replace($token, (string) $value, (string) $signature);
        }

        return $signature;
    }

    public function getFrom(?Email $email): AddressDTO
    {
        if ($email && $email->getFromAddress()) {
            return new AddressDTO($email->getFromAddress(), $email->getFromName());
        }

        return $this->getDefaultFrom();
    }

    /**
     * @param array<string,mixed>|null $contact
     */
    private function getDefaultFrom(?array $contact = null): AddressDTO
    {
        $systemDefault = $this->defaultFrom ?: $this->getSystemDefaultFrom();

        if ($systemDefault->isEmailTokenized() || $systemDefault->isNameTokenized()) {
            return $this->resolveTokensInAddress($systemDefault, $contact);
        }

        return $systemDefault;
    }

    private function getSystemDefaultFrom(): AddressDTO
    {
        $email = $this->coreParametersHelper->get('mailer_from_email');
        $name  = $this->coreParametersHelper->get('mailer_from_name') ?: null;

        return new AddressDTO($email, $name);
    }

    /**
     * @param mixed[] $contact
     */
    private function getEmailFromToken(AddressDTO $address, ?array $contact = null, bool $asOwner = true, ?Email $email = null): AddressDTO
    {
        try {
            $name = $address->isNameTokenized() ? $address->getNameTokenValue($contact) : $address->getName();
        } catch (TokenNotFoundOrEmptyException) {
            $name = $this->defaultFrom ? $this->defaultFrom->getName() : $this->getSystemDefaultFrom()->getName();
        }

        try {
            $emailAddress = $address->isEmailTokenized() ? $address->getEmailTokenValue($contact) : $address->getEmail();

            return new AddressDTO($emailAddress, $name);
        } catch (TokenNotFoundOrEmptyException) {
            if ($contact && $asOwner) {
                try {
                    return $this->getFromEmailAsOwner($contact, $email);
                } catch (OwnerNotFoundException) {
                }
            }

            return $this->getDefaultFrom($contact);
        }
    }

    /**
     * @param mixed[] $contact
     */
    private function resolveTokensInAddress(AddressDTO $address, ?array $contact = null): AddressDTO
    {
        try {
            $emailAddress = $address->isEmailTokenized() ? $address->getEmailTokenValue($contact) : $address->getEmail();
        } catch (TokenNotFoundOrEmptyException) {
            $emailAddress = '';
        }

        if (!$emailAddress) {
            // Token had no value and no default — fall back to raw system default
            return $this->getSystemDefaultFrom();
        }

        try {
            $name = $address->isNameTokenized() ? $address->getNameTokenValue($contact) : $address->getName();
        } catch (TokenNotFoundOrEmptyException) {
            $name = '';
        }

        return new AddressDTO($emailAddress, $name);
    }

    /**
     * @param array<string,mixed> $contact
     */
    private function getFromEmailAsOwner(array $contact, ?Email $email = null): AddressDTO
    {
        if (empty($contact['owner_id'])) {
            throw new OwnerNotFoundException();
        }

        $owner      = $this->getContactOwner((int) $contact['owner_id'], $email);
        $ownerEmail = $owner['email'];
        $ownerName  = sprintf('%s %s', $owner['first_name'], $owner['last_name']);

        // Decode apostrophes and other special characters
        $ownerName = trim(html_entity_decode($ownerName, ENT_QUOTES));

        return new AddressDTO($ownerEmail, $ownerName);
    }
}
