<?php

namespace Mautic\SmsBundle\Collection;

use Mautic\SmsBundle\Exception\RecipientNotFoundException;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;

class RecipientCollection extends \ArrayIterator
{
    /**
     * @return array<SmsRecipientDTO>
     */
    public function toChoices(): array
    {
        $choices = [];

        /** @var SmsRecipientDTO $recipient */
        foreach ($this as $recipient) {
            $choices[$recipient->getKey()] = $recipient;
        }

        return $choices;
    }

    /**
     * @throws RecipientNotFoundException
     */
    public function getFieldByKey(int $key): SmsRecipientDTO
    {
        /** @var SmsRecipientDTO $recipient */
        foreach ($this as $recipient) {
            if ($key === $recipient->getKey()) {
                return $recipient;
            }
        }

        throw new RecipientNotFoundException("Recipient with key {$key} was not found.");
    }
}
