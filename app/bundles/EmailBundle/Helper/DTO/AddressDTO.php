<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper\DTO;

use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;
use Symfony\Component\Mime\Address;

final class AddressDTO
{
    private ?string $name = null;

    public function __construct(private string $email, ?string $name = null)
    {
        $this->setName($name);
    }

    /**
     * @param array<string,?string> $address
     */
    public static function fromAddressArray(array $address): self
    {
        $email = key($address);

        if (!$email) {
            throw new \InvalidArgumentException('Address array must have an email as key');
        }

        return new self($email, $address[$email] ?? null);
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function isEmpty(): bool
    {
        return empty($this->email);
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
        return (bool) ($this->name ? preg_match('/{contactfield=(.*?)}/', $this->name) : false);
    }

    /**
     * @return array<string,?string>
     */
    public function getAddressArray(): array
    {
        return [$this->email => $this->name];
    }

    public function toMailerAddress(): Address
    {
        return new Address($this->email, $this->name ?? '');
    }

    /**
     * Decode apostrophes and other special characters.
     */
    public function setName(?string $name): void
    {
        if (!$name) {
            $this->name = null;

            return;
        }

        $this->name = trim(html_entity_decode($name, ENT_QUOTES));
    }
}
