<?php

namespace Mautic\LeadBundle\Segment;

class RandomParameterName
{
    /**
     * @var int
     */
    protected $lastUsedParameterId = 0;

    /**
     * Generate a unique parameter name from int using base conversion.
     * This eliminates chance for parameter name collision and provides unique result for each number.
     *
     * @see https://stackoverflow.com/questions/307486/short-unique-id-in-php/1516430#1516430
     */
    public function generateRandomParameterName(): string
    {
        $value = base_convert((string) $this->lastUsedParameterId, 10, 36);

        ++$this->lastUsedParameterId;

        return 'par'.$value;
    }
}
