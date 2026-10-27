<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Sms;

use Mautic\SmsBundle\Collection\RecipientCollection;
use Mautic\SmsBundle\Helper\DTO\SmsRecipientDTO;

interface BulkTransportInterface extends TransportInterface
{
    /**
     * @param RecipientCollection<SmsRecipientDTO> $collection
     *
     * @return RecipientCollection<SmsRecipientDTO>
     */
    public function sendBatchSms(RecipientCollection $collection, string $content): RecipientCollection;
}
