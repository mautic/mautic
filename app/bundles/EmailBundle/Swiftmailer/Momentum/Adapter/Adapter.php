<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Adapter;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use SparkPost\SparkPost;
use SparkPost\SparkPostPromise;

/**
 * Class Adapter.
 */
final class Adapter implements AdapterInterface
{
    /**
     * @var SparkPost
     */
    private $momentumSparkpost;

    /**
     * Adapter constructor.
     */
    public function __construct(SparkPost $momentumSparkpost)
    {
        $this->momentumSparkpost   = $momentumSparkpost;
    }

    /**
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO)
    {
        $payload = json_decode(json_encode($transmissionDTO), true);

        return $this->momentumSparkpost->transmissions->post($payload);
    }
}
