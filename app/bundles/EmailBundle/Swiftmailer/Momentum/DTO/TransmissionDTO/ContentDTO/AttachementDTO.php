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
 * Class AttachementDTO.
 */
final class AttachementDTO implements \JsonSerializable
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $content;

    /**
     * AttachementDTO constructor.
     *
     * @param string $type
     * @param string $name
     * @param string $content
     */
    public function __construct($type, $name, $content)
    {
        $this->type    = $type;
        $this->name    = $name;
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'data' => $this->content,
        ];
    }
}
