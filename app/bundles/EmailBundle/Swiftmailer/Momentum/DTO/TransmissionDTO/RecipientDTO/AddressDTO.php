<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

final class AddressDTO implements \JsonSerializable
{
    private ?string $headerTo = null;

    public function __construct(private string $email, private string $name, bool $bcc = false)
    {
        if (false === $bcc) {
            $this->headerTo = $email;
        }
    }

    /**
     * @return array<string, string|bool>
     */
    public function jsonSerialize(): array
    {
        $json = [
            'email' => $this->email,
            'name'  => $this->name,
        ];
        if (null !== $this->headerTo) {
            $json['header_to'] = $this->headerTo;
        }

        return $json;
    }
}
