<?php

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
