<?php

namespace Mautic\EmailBundle\Entity\EmailHeader;

use Mautic\EmailBundle\Exception\EmailHeaderValidationException;

/**
 * Interface EmailHeaderValidatorInterface.
 */
interface EmailHeaderValidatorInterface
{
    /**
     * @param EmailHeader $header
     *
     * @throws EmailHeaderValidationException
     */
    public function validate(EmailHeader $header);
}
