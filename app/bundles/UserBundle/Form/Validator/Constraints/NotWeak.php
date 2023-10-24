<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Symfony\Component\Validator\Constraint;

class NotWeak extends Constraint
{
    public const TOO_WEAK = 'f61e730a-284e-11eb-adc1-0242ac120002';

    protected static $errorNames = [
        self::TOO_WEAK => 'PASSWORD_TOO_WEAK_ERROR',
    ];

    public $message = 'This password is too weak. Consider using a stronger password.';

    public $score = PasswordStrengthEstimatorModel::MINIMUM_PASSWORD_STRENGTH_ALLOWED;
}
