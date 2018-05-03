<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientsDTO\AddressDTO;

/**
 * Class RecipientsDTO.
 */
final class RecipientsDTO implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $returnPath = null;

    /**
     * @var array
     */
    private $addresses = [];

    /**
     * @var array
     */
    private $tags = [];

    /**
     * @var array
     */
    private $metadata = [];

    /**
     * @var array
     */
    private $substitutionData = [];

    /**
     * @param null|string $returnPath
     *
     * @return RecipientsDTO
     */
    public function setReturnPath($returnPath)
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    /**
     * @param AddressDTO $address
     *
     * @return RecipientsDTO
     */
    public function addAddress(AddressDTO $address)
    {
        $this->addresses[] = $address;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return RecipientsDTO
     */
    public function addTag($key, $value)
    {
        $this->tags[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return RecipientsDTO
     */
    public function addMetadata($key, $value)
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addSubstitutionData($key, $value)
    {
        $this->substitutionData[$key] = $value;

        return $this;
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        $json = [
            'address' => $this->addresses,
        ];
        if (count($this->tags) !== 0) {
            $json['tags'] = $this->tags;
        }
        if (count($this->metadata) !== 0) {
            $json['metadata'] = $this->metadata;
        }
        if (count($this->substitutionData) !== 0) {
            $json['substitution_data'] = $this->substitutionData;
        }
        if ($this->returnPath !== null) {
            $json['return_path'] = $this->returnPath;
        }

        return $json;
    }
}
