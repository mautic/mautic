<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO\AddressDTO;

/**
 * Class RecipientDTO.
 */
final class RecipientDTO implements \JsonSerializable
{
    /**
     * @var string|null
     */
    private $returnPath = null;

    /**
     * @var AddressDTO
     */
    private $address;

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
     * RecipientDTO constructor.
     *
     * @param AddressDTO $addressDTO
     * @param array      $metadata
     * @param array      $substitutionData
     */
    public function __construct(AddressDTO $addressDTO, $metadata = [], $substitutionData = [])
    {
        $this->address          = $addressDTO;
        $this->metadata         = $metadata;
        $this->substitutionData = $substitutionData;
    }

    /**
     * @param null|string $returnPath
     *
     * @return RecipientDTO
     */
    public function setReturnPath($returnPath)
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return RecipientDTO
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
     * @return RecipientDTO
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
            'address' => $this->address,
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
