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

    public function validate(string $password, int $score = self::MINIMUM_PASSWORD_STRENGTH_ALLOWED, array $dictionary = self::DICTIONARY): bool
    {
        return $score <= $this->passwordStrengthEstimator->passwordStrength($password, $this->sanitazeDictionary($dictionary))['score'];
    }

    private function sanitazeDictionary(array $dictionary): array
    {
        return array_unique(array_filter($dictionary));
    }
}
