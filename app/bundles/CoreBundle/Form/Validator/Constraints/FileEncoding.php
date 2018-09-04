<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FileEncoding extends Constraint
{
    public $encodingFormatMessage = 'mautic.core.invalid_file_encoding';
    public $encodingFormat        = '[UTF-8]';

    public function validatedBy()
    {
        return FileEncodingValidator::class;
    }
}
