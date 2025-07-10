<?php

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FileEncoding extends Constraint
{
    public $encodingFormatMessage = 'mautic.core.invalid_file_encoding';

    public $encodingFormat        = '[UTF-8]';

    public function validatedBy(): string
    {
        return FileEncodingValidator::class;
    }
}
