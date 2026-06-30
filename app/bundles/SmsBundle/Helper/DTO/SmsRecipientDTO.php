<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Helper\DTO;

use Mautic\LeadBundle\Entity\Lead;

final class SmsRecipientDTO implements \JsonSerializable
{
    private bool $result = false;

    /**
     * @param array<mixed> $substitutionData
     */
    public function __construct(private readonly Lead $lead, private readonly array $substitutionData, private readonly string $finalMessage)
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

    public function jsonSerialize(): mixed
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

    /**
     * Returns SMS message with replaced tokens for the recipient.
     */
    public function getFinalMessage(): string
    {
        return $this->finalMessage;
    }
}
