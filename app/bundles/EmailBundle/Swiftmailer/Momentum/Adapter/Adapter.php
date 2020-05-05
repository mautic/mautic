<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     *
     * @param SparkPost $momentumSparkpost
     */
    public function __construct(SparkPost $momentumSparkpost)
    {
        $this->momentumSparkpost   = $momentumSparkpost;
    }

    /**
     * @param TransmissionDTO $transmissionDTO
     *
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO)
    {
        $payload = json_decode(json_encode($transmissionDTO), true);

        return $this->momentumSparkpost->transmissions->post($payload);
    }
}
