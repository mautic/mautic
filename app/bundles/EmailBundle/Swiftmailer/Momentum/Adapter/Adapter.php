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
    private $sparkpost;

    /**
     * MomentumAdapter constructor.
     *
     * @param SparkPost $momentumSparkpost
     */
    public function __construct(Sparkpost $momentumSparkpost)
    {
        $this->sparkpost = $momentumSparkpost;
    }

    /**
     * @param TransmissionDTO $transmissionDTO
     *
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO)
    {
        dump($this->sparkpost);
        exit;

        return $this->sparkpost->transmissions->post(json_encode($transmissionDTO));
    }
}
