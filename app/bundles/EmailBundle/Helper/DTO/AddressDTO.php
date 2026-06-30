<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper\DTO;

use Mautic\EmailBundle\Helper\Exception\TokenNotFoundOrEmptyException;
use Mautic\LeadBundle\Helper\TokenHelper;
use Symfony\Component\Mime\Address;

final class AddressDTO
{
    private ?string $name = null;

    public function __construct(private readonly string $email, ?string $name = null)
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
     * @param array<string,mixed>|null $contact
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getNameTokenValue(?array $contact = null): string
    {
        return $this->getTokenValue($this->getName(), $contact);
    }

    /**
     * @param array<string,mixed>|null $contact
     *
     * @throws TokenNotFoundOrEmptyException
     */
    public function getEmailTokenValue(?array $contact = null): string
    {
        return $this->getTokenValue($this->getEmail(), $contact);
    }

    /**
     * @param array<string,mixed>|null $contact
     *
     * @throws TokenNotFoundOrEmptyException
     */
    private function getTokenValue(?string $content, ?array $contact = null): string
    {
        if (!$content || !TokenHelper::validToken($content)) {
            throw new TokenNotFoundOrEmptyException();
        }

        if ($contact) {
            $tokenValue = TokenHelper::findLeadTokens($content, $contact, true);
        } else {
            // Use a non-empty lead array so token defaults are resolved while preserving the whole content.
            $tokenValue = TokenHelper::findLeadTokens($content, ['id' => 0], true);
        }

        $tokenValue = trim((string) $tokenValue);

        if ('' === $tokenValue) {
            throw new TokenNotFoundOrEmptyException(sprintf('%s was not found or empty in the contact array', TokenHelper::getTokenFieldAlias($content)));
        }

        return $tokenValue;
    }

    public function isEmailTokenized(): bool
    {
        return $this->email && preg_match('/{contactfield=(.*?)}/', $this->email);
    }

    public function isNameTokenized(): bool
    {
        return $this->name && preg_match('/{contactfield=(.*?)}/', $this->name);
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
