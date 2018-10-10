<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    private $headerTo = null;

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
        if ($bcc === false) {
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
        if ($this->headerTo !== null) {
            $json['header_to'] = $this->headerTo;
        }

        return $json;
    }
}
