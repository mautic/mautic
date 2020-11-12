<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\EventListener;

use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use ZxcvbnPhp\Zxcvbn as PasswordStrengthEstimator;

class PasswordSubscriber implements EventSubscriberInterface
{
    private const MINIMUM_PASSWORD_STRENGTH_ALLOWED = 3;

    /**
     * @var PasswordStrengthEstimator
     */
    private $passwordStrengthEstimator;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var CompanyRepository
     */
    private $companyRepository;

    public function __construct(PasswordStrengthEstimator $passwordStrengthEstimator, UserRepository $userRepository, CompanyRepository $companyRepository)
    {
        $this->passwordStrengthEstimator = $passwordStrengthEstimator;
        $this->userRepository            = $userRepository;
        $this->companyRepository         = $companyRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_FORM_AUTHENTICATION => ['onUserFormAuthentication', 999],
        ];
    }

    public function onUserFormAuthentication(AuthenticationEvent $authenticationEvent): void
    {
        $credentials = $authenticationEvent->getToken()->getCredentials();
        if (!is_string($credentials)) {
            return;
        }

        $user       = $this->initializeUser($authenticationEvent);
        $dictionary = $user ? $this->buildDictionary($user) : [];

        $score = $this->passwordStrengthEstimator->passwordStrength($credentials, $dictionary)['score'];

        if (static::MINIMUM_PASSWORD_STRENGTH_ALLOWED <= $score) {
            // The password is fine, bail.
            return;
        }

        $authenticationEvent->setIsFailedAuthentication(true);
        $authenticationEvent->setFailedAuthenticationMessage('Weak password');
        $authenticationEvent->stopPropagation();
    }

    private function buildDictionary(User $user): array
    {
        $dictionary = [
            $user->getEmail(),
            $user->getUsername(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPosition(),
            $user->getSignature(),
        ];

        return array_unique(array_filter($dictionary));
    }

    private function initializeUser(AuthenticationEvent $authenticationEvent): ?User
    {
        $token = $authenticationEvent->getToken();
        if ($token->getUser() instanceof User) {
            return $token->getUser();
        }

        if ($token->getUsername()) {
            return $this->userRepository->findOneByUsername($token->getUsername()) ?? null;
        }

        return null;
    }
}
