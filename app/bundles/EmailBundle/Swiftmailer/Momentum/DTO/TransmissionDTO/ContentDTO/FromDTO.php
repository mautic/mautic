<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\ContentDTO;

/**
 * Class FromDTO.
 */
final class FromDTO implements \JsonSerializable
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
     * FromDTO constructor.
     *
     * @param string $email
     */
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * @param string|null $name
     *
     * @return FromDTO
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
