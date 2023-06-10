<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO\AddressDTO;

final class RecipientDTO implements \JsonSerializable
{
    private ?string $returnPath = null;

    /**
     * @var array<string, string>
     */
    private array $tags = [];

    /**
     * RecipientDTO constructor.
     *
     * @param array<string, mixed> $metadata
     * @param array<string, mixed> $substitutionData
     */
    public function __construct(private AddressDTO $address, private array $metadata = [], private array $substitutionData = [])
    {
    }

    public function setReturnPath(?string $returnPath): self
    {
        $this->returnPath = $returnPath;

        return $this;
    }

    public function addTag(string $key, string $value): self
    {
        $this->tags[$key] = $value;

        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;

        return $this;
    }

    public function addSubstitutionData(string $key, mixed $value): self
    {
        $this->substitutionData[$key] = $value;

        return $this;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $json = [
            'address' => $this->address,
        ];
        if (0 !== count($this->tags)) {
            $json['tags'] = $this->tags;
        }
        if (0 !== count($this->metadata)) {
            $json['metadata'] = $this->metadata;
        }

        if (0 === count($this->substitutionData)) {
            // `substitution_data` is required but Sparkpost will return the following error with empty arrays:
            // field 'substitution_data' is of type 'json', but needs to be of type 'json_object'
            $json['substitution_data'] = new \stdClass();
        } else {
            $json['substitution_data'] = $this->substitutionData;
        }

        if (null !== $this->returnPath) {
            $json['return_path'] = $this->returnPath;
        }

        return $json;
    }
}
