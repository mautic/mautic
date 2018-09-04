<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;

/**
 * Interface SwiftMessageValidatorInterface.
 */
interface SwiftMessageValidatorInterface
{
    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws SwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message);
}
