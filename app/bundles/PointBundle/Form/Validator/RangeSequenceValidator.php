<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\PointBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class RangeSequenceValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint)
    {
        if ($value->getFromScore() >= $value->getToScore()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('fromScore')
                ->addViolation();

            $this->context->buildViolation($constraint->message)
                ->atPath('toScore')
                ->addViolation();
        }
    }

    public function validatedBy()
    {
        return 'point_range_sequence';
    }
}