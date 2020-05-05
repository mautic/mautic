<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;

/**
 * Interface SwiftMessageServiceInterface.
 */
interface SwiftMessageServiceInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @return TransmissionDTO
     */
    public function transformToTransmission(\Swift_Mime_Message $message);
}
