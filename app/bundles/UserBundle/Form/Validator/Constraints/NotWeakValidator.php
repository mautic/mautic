<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use ZxcvbnPhp\Zxcvbn as PasswordStrengthEstimator;

class NotWeakValidator extends ConstraintValidator
{
    private const MINIMUM_SCORE = 3;

    /**
     * @var PasswordStrengthEstimator
     */
    private $passwordStrengthEstimator;

    /**
     * @var
     */
    private $minimumScore;

    public function __construct(PasswordStrengthEstimator $passwordStrengthEstimator)
    {
        $this->passwordStrengthEstimator = $passwordStrengthEstimator;
        $this->minimumScore              = self::MINIMUM_SCORE;
    }

    public function setMinimumScore(int $minimumScore): void
    {
        $this->minimumScore = max(0, $minimumScore);
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotWeak) {
            throw new UnexpectedTypeException($constraint, NotWeak::class);
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $this->formatValue($value))
            ->setCode(NotBlank::IS_BLANK_ERROR)
            ->addViolation();
    }
}
