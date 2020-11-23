<?php

declare(strict_types=1);

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use ZxcvbnPhp\Zxcvbn as PasswordStrengthEstimator;

class NotWeakValidator extends ConstraintValidator
{
    /**
     * @var PasswordStrengthEstimator
     */
    private $passwordStrengthEstimatorModel;

    public function __construct(PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel)
    {
        $this->passwordStrengthEstimatorModel = $passwordStrengthEstimatorModel;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotWeak) {
            throw new UnexpectedTypeException($constraint, NotWeak::class);
        }

        if ($this->passwordStrengthEstimatorModel->validate($value, $constraint->score)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(NotWeak::TOO_WEAK)
            ->addViolation();
    }
}
