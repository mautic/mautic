<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientsDTO;

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
     * @var string|null
     */
    private $name = null;

    /**
     * @var string|null
     */
    private $headerTo = null;

    /**
     * AddressDTO constructor.
     *
     * @param string $email
     * @param bool   $isBcc
     */
    public function __construct($email, $isBcc = false)
    {
        $this->email = $email;
        if ($isBcc === false) {
            $this->headerTo = $email;
        }
    }

    /**
     * @param null|string $name
     *
     * @return AddressDTO
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $json = [
            'email' => $this->email,
        ];
        if ($this->name !== null) {
            $json['name'] = $this->name;
        }

        return $json;
    }
}
