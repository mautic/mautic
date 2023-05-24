<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;

final class FromDTO implements \JsonSerializable
{
    private string $email;

    private ?string $name = null;

    public function __construct(string $email)
    {
        $this->email = $email;
    }

    public function setName(?string $name = null): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        $json = [
            'email' => $this->email,
        ];
        if (null !== $this->name) {
            $json['name'] = $this->name;
        }

        return $json;
    }
}
