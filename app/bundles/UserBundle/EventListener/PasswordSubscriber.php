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

use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use ZxcvbnPhp\Zxcvbn as PasswordStrengthEstimator;

class PasswordSubscriber implements EventSubscriberInterface
{
    private const MINIMUM_PASSWORD_STRENGTH_ALLOWED = 3;

    private const DICTIONARY = [
        'mautic',
        'acquia',
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

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Router
     */
    private $router;

    public function __construct(PasswordStrengthEstimator $passwordStrengthEstimator, UserRepository $userRepository, Router $router)
    {
        $this->passwordStrengthEstimator = $passwordStrengthEstimator;
        $this->userRepository            = $userRepository;
        $this->router                    = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_FORM_AUTHENTICATION => ['onUserFormAuthentication', 999],
            UserEvents::USER_PRE_AUTHENTICATION  => ['onUserFormAuthentication', 999],
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

        $authenticationException = new AuthenticationException('Weak password');
        $authenticationException->setToken($authenticationEvent->getToken());
        throw $authenticationException;
    }

    private function buildDictionary(User $user): array
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
