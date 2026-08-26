<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class NotWeak extends Constraint
{
    public const string TOO_WEAK = 'f61e730a-284e-11eb-adc1-0242ac120002';

    protected const ERROR_NAMES = [
        self::TOO_WEAK => 'PASSWORD_TOO_WEAK_ERROR',
    ];

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        public string $message = 'This password is too weak. Consider using a stronger password.',
        public int $score = PasswordStrengthEstimatorModel::MINIMUM_PASSWORD_STRENGTH_ALLOWED,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }
}
