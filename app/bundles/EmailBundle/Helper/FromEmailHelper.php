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
    public function getFromAddressConsideringOwner(AddressDTO $address, array $contact = null, Email $email = null): AddressDTO
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
    public function getFromAddressDto(AddressDTO $address, array $contact = null, Email $email = null): AddressDTO
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
    public function getContactOwner(int $userId, Email $email = null): array
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

    public function getSignature(): string
    {
        if (!$this->lastOwner) {
            // No owner context, so no owner signature can be used.
            // Fallback to a global signature if one is defined.
            // Assuming 'mautic.default_signature' is the configuration parameter for the global signature.
            $globalSignature = (string) $this->coreParametersHelper->get('default_signature', '');
            if (!empty(trim($globalSignature))) {
                // For a truly global signature, there's no specific owner context for |USER_...| tokens.
                // We can pass an empty owner array to replaceSignatureTokens or handle it specifically.
                // Here, we pass a minimal owner array containing only what might be relevant for |FROM_NAME| if used in global.
                $fromName = $this->defaultFrom ? $this->defaultFrom->getName() : $this->getSystemDefaultFrom()->getName();
                $minimalOwnerContext = ['first_name' => '', 'last_name' => $fromName]; // Or derive from default mailer settings
                return $this->replaceSignatureTokens($minimalOwnerContext, $globalSignature, true);
            }
            return '';
        }

        // Owner context exists, try to use their signature
        $ownerSignatureTemplate = $this->lastOwner['signature'] ?? '';

        if (empty(trim($ownerSignatureTemplate))) {
            // Owner's signature is empty or only whitespace, try global fallback
            $globalSignature = (string) $this->coreParametersHelper->get('default_signature', '');
            if (!empty(trim($globalSignature))) {
                // Process global signature, but still with the context of the current $this->lastOwner
                // so |USER_...| tokens and |FROM_NAME| (as owner's name) can be used if the global sig contains them.
                return $this->replaceSignatureTokens($this->lastOwner, $globalSignature);
            }
            return ''; // No owner signature and no global signature
        }

        // Process the owner's specific signature using their own signature template
        return $this->replaceSignatureTokens($this->lastOwner, $ownerSignatureTemplate);
    }

    /**
     * @param mixed[] $owner
     * @param string  $signatureTemplate The signature string to process (either owner's or global default)
     * @param bool    $isGlobalContext   Indicates if the signatureTemplate is a global one without specific owner context for USER tokens
     */
    private function replaceSignatureTokens(array $owner, string $signatureTemplate, bool $isGlobalContext = false): string
    {
        $processedSignature = nl2br($signatureTemplate);

        // |FROM_NAME| should use the owner's name if available, otherwise mailer default from name if it's a global signature
        $ownerFullName = trim(($owner['first_name'] ?? '') . ' ' . ($owner['last_name'] ?? ''));
        if (!empty($ownerFullName)) {
            $processedSignature = str_replace('|FROM_NAME|', $ownerFullName, $processedSignature);
        } elseif ($isGlobalContext) {
            // For a global signature with no specific owner, |FROM_NAME| might refer to the general sender name
            $globalFromName = $this->defaultFrom ? $this->defaultFrom->getName() : $this->getSystemDefaultFrom()->getName();
            $processedSignature = str_replace('|FROM_NAME|', $globalFromName ?: '', $processedSignature);
        } else {
            // Fallback for |FROM_NAME| if owner details are somehow empty but it's not strictly a global context
            $processedSignature = str_replace('|FROM_NAME|', '', $processedSignature);
        }

        // Process |USER_...| tokens with owner's data only if not a global context being minimally processed
        // or if it's a global template but we still want to try filling owner details if they exist for some reason
        if (!$isGlobalContext || !empty($owner)) {
            foreach ($owner as $key => $value) {
                $token     = sprintf('|USER_%s|', strtoupper($key));
                $processedSignature = str_replace($token, (string) $value, $processedSignature);
            }
        }

        // Example of a truly global static token that could be added
        // if ($isGlobalContext || $isOwnerSignatureEmptyAndGlobalUsed) {
        //    $siteUrl = $this->coreParametersHelper->get('site_url');
        //    $processedSignature = str_replace('|MAUTIC_URL|', $siteUrl ?: '', $processedSignature);
        // }

        return $processedSignature;
    }

    public function getFrom(?Email $email): AddressDTO
    {
        if ($email && $email->getFromAddress()) {
            return new AddressDTO($email->getFromAddress(), $email->getFromName());
        }

        return $this->getDefaultFrom();
    }

    private function getDefaultFrom(): AddressDTO
    {
        if ($this->defaultFrom) {
            return $this->defaultFrom;
        }

        return $this->getSystemDefaultFrom();
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
    private function getEmailFromToken(AddressDTO $address, array $contact = null, bool $asOwner = true, Email $email = null): AddressDTO
    {
        try {
            if (!$contact) {
                throw new TokenNotFoundOrEmptyException();
            }

            $name = $address->isNameTokenized() ? $address->getNameTokenValue($contact) : $address->getName();
        } catch (TokenNotFoundOrEmptyException) {
            $name = $this->defaultFrom ? $this->defaultFrom->getName() : $this->getSystemDefaultFrom()->getName();
        }

        try {
            if (!$contact) {
                throw new TokenNotFoundOrEmptyException();
            }

            $emailAddress = $address->isEmailTokenized() ? $address->getEmailTokenValue($contact) : $address->getEmail();

            return new AddressDTO($emailAddress, $name);
        } catch (TokenNotFoundOrEmptyException) {
            if ($contact && $asOwner) {
                try {
                    return $this->getFromEmailAsOwner($contact, $email);
                } catch (OwnerNotFoundException) {
                }
            }

            return $this->getDefaultFrom();
        }
    }

    /**
     * @param mixed[] $contact
     *
     * @throws OwnerNotFoundException
     */
    private function getFromEmailAsOwner(array $contact, Email $email = null): AddressDTO
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
