<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Model;

use Mautic\UserBundle\Entity\User;
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

    /**
     * @var PasswordStrengthEstimator
     */
    private $passwordStrengthEstimator;

    public function __construct(PasswordStrengthEstimator $passwordStrengthEstimator)
    {
        $this->passwordStrengthEstimator = $passwordStrengthEstimator;
    }

    public function validate(string $password, int $score = self::MINIMUM_PASSWORD_STRENGTH_ALLOWED, $dictionary = []): bool
    {
        return $score <= $this->passwordStrengthEstimator->passwordStrength($password, $dictionary)['score'];
    }

    public function validateUser(string $userPassword, User $user = null, int $score = self::MINIMUM_PASSWORD_STRENGTH_ALLOWED): bool
    {
        return $this->validate($userPassword, $score, $this->buildUserPasswordDictionary($user));
    }

    private function buildUserPasswordDictionary(User $user): array
    {
        $dictionary = array_merge([
            $user->getEmail(),
            $user->getUsername(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPosition(),
            $user->getSignature(),
        ], static::DICTIONARY);

        return array_unique(array_map('mb_strtolower', array_filter($dictionary)));
    }
}
