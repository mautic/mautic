<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LengthValidator as SymfonyLengthValidator;

class LengthValidator extends SymfonyLengthValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (is_array($value)) {
            $value = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, $value);
        }

        parent::validate($value, $constraint);
    }
}
