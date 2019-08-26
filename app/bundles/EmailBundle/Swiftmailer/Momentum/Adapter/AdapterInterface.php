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
use SparkPost\SparkPostPromise;

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface
{
    /**
     * @param TransmissionDTO $transmissionDTO
     *
     * @return SparkPostPromise
     */
    public function createTransmission(TransmissionDTO $transmissionDTO);
}
