<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\DTO;

use Mautic\LeadBundle\Entity\Lead;

class SmsRecipientDTO implements \JsonSerializable
{
    /**
     * @var bool
     */
    private $result = false;

    /**
     * RecipientDTO constructor.
     *
     * @param array<mixed> $substitutionData
     */
    public function __construct(private Lead $lead, private array $substitutionData = [])
    {
    }

    public function getKey(): int
    {
        return $this->lead->getId();
    }

    public function getLead(): Lead
    {
        return $this->lead;
    }

    public function setResult(bool $result): void
    {
        $this->result = $result;
    }

    public function getResult(): bool
    {
        return $this->result;
    }

    /**
     * @return mixed[]
     */
    public function getSubstitutionData(): array
    {
        return $this->substitutionData;
    }

    /**
     * @return mixed[]
     */
    public function jsonSerialize(): array
    {
        $json = [
            'lead'   => $this->lead,
            'result' => $this->result,
        ];

        if (0 === count($this->substitutionData)) {
            // `substitution_data` is required but Sparkpost will return the following error with empty arrays:
            // field 'substitution_data' is of type 'json_array', but needs to be of type 'json_object'
            $json['substitution_data'] = new \stdClass();
        } else {
            $json['substitution_data'] = $this->substitutionData;
        }

        return $json;
    }
}
