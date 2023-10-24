<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Model;

use ZxcvbnPhp\Zxcvbn as PasswordStrengthEstimator;

class PasswordStrengthEstimatorModel
{
    public const MINIMUM_PASSWORD_STRENGTH_ALLOWED = 3;

    private const DICTIONARY = [
        'mautic',
        'user',
        'lead',
        'bundle',
        'campaign',
        'company',
    ];

    private PasswordStrengthEstimator $passwordStrengthEstimator;

    public function __construct()
    {
        $this->passwordStrengthEstimator = new PasswordStrengthEstimator();
    }

    public function validate(?string $password, int $score = self::MINIMUM_PASSWORD_STRENGTH_ALLOWED, array $dictionary = self::DICTIONARY): bool
    {
        return $score <= $this->passwordStrengthEstimator->passwordStrength($password, $this->sanitazeDictionary($dictionary))['score'];
    }

    private function sanitazeDictionary(array $dictionary): array
    {
        return array_unique(array_filter($dictionary));
    }
}
