<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper\DTO;

use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;

final class AddressDTO
{
    public function __construct(private string $email, private ?string $name = null)
    {
    }

    /**
     * @param array<string,?string> $address
     */
    public static function fromAddressArray(array $address): self
    {
        $email = key($address);
        $name  = $address[$email] ?? null;

        if ($name) {
            // Decode apostrophes and other special characters
            $name = trim(html_entity_decode($name, ENT_QUOTES));
        }

        return new self($email, $name);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param array<string,mixed> $contact
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getEmailTokenValue(array $contact): string
    {
        if (!preg_match('/{contactfield=(.*?)}/', $this->email, $matches)) {
            throw new TokenNotFoundOrEmptyException();
        }

        $emailToken = $matches[1];

        if (empty($contact[$emailToken])) {
            throw new TokenNotFoundOrEmptyException("$emailToken was not found or empty in the contact array");
        }

        return $contact[$emailToken];
    }

    /**
     * @param array<string,mixed> $contact
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getNameTokenValue(array $contact): string
    {
        if (!preg_match('/{contactfield=(.*?)}/', $this->name, $matches)) {
            throw new TokenNotFoundOrEmptyException();
        }

        $nameToken = $matches[1];

        if (empty($contact[$nameToken])) {
            throw new TokenNotFoundOrEmptyException("$nameToken was not found or empty in the contact array");
        }

        return $contact[$nameToken];
    }

    public function isEmailTokenized(): bool
    {
        return (bool) preg_match('/{contactfield=(.*?)}/', $this->email);
    }

    public function isNameTokenized(): bool
    {
        return (bool) preg_match('/{contactfield=(.*?)}/', $this->name);
    }

    /**
     * @return array<string,?string>
     */
    public function getAddressArray(): array
    {
        return [$this->email => $this->name];
    }
}
