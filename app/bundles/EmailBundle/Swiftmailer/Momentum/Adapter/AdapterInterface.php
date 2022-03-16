<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use SparkPost\SparkPostPromise;

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface
{
    /**
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO);
}
