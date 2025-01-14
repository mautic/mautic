<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

/**
 * Class AddressDTO.
 */
final class AddressDTO implements \JsonSerializable
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $headerTo;

    /**
     * AddressDTO constructor.
     *
     * @param string $email
     * @param string $name
     * @param bool   $bcc
     */
    public function __construct($email, $name, $bcc = false)
    {
        $this->email = $email;
        $this->name  = $name;
        if (false === $bcc) {
            $this->headerTo = $email;
        }
    }

    /**
     * @return array
     */
    public function jsonSerialize()
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
