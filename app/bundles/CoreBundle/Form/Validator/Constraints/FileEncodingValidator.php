<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Throws an exception if the field alias is equal some segment filter keyword.
 * It would cause odd behavior with segment filters otherwise.
 */
class FileEncodingValidator extends ConstraintValidator
{
    /**
     * @param LeadField $field
     */
    public function validate($field, Constraint $constraint): void
    {
        /*
            If the file uploaded exceeds the max size, it will not be considered,
            and the file path will be an empty string "". If that is the case
            no further checks are required. Just return.
        */
        if (!$field || '' === $field->getPathname() || null === $field->getPathname()) {
            return;
        }

        // Open the file in "reading only" mode
        $fileHandle = fopen($field->getPathname(), 'rb');

        // Handler is valid or not
        if (false === $fileHandle) {
            return;
        }

        // While we are not yet at the end of the file
        while (!feof($fileHandle)) {
            // Read the current line
            $line = fgets($fileHandle);

            // Check for UTF-8 encoding
            if (!mb_check_encoding($line, 'UTF-8')) {
                $this->context->addViolation($constraint->encodingFormatMessage);
            }
        }

        // Finally, close the file handle.
        fclose($fileHandle);
    }
}
