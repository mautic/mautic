<?php

namespace Mautic\EmailBundle\Entity\EmailHeader;

use Mautic\EmailBundle\Exception\EmailHeaderValidationException;

/**
 * Class EmailHeaderValidator.
 */
final class EmailHeaderValidator implements EmailHeaderValidatorInterface
{
    /**
     * @param EmailHeader $emailHeader
     *
     * @throws EmailHeaderValidationException
     */
    public function validate(EmailHeader $emailHeader)
    {
        if ($emailHeader->getEmail() === null) {
            throw new EmailHeaderValidationException(EmailHeader::class." - Email property can't be null");
        }
        if ($emailHeader->getName() !== null) {
            throw new EmailHeaderValidationException(EmailHeader::class." - Name property can't be null");
        }
        if ($emailHeader->getValue() !== null) {
            throw new EmailHeaderValidationException(EmailHeader::class." - Value property can't be null");
        }
    }
}
