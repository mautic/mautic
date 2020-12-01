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
use Mautic\UserBundle\Exception\WeakPasswordException;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Router;

class PasswordSubscriber implements EventSubscriberInterface
{
    /**
     * @var PasswordStrengthEstimatorModel
     */
    private $passwordStrengthEstimatorModel;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Router
     */
    private $router;

    public function __construct(PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel, UserRepository $userRepository, Router $router)
    {
        $this->passwordStrengthEstimatorModel = $passwordStrengthEstimatorModel;
        $this->userRepository                 = $userRepository;
        $this->router                         = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserEvents::USER_FORM_POST_LOCAL_PASSWORD_AUTHENTICATION => ['onUserFormAuthentication', 0],
        ];
    }

    public function onUserFormAuthentication(AuthenticationEvent $authenticationEvent): void
    {
        $userPassword = $authenticationEvent->getToken()->getCredentials();
        if (!is_string($userPassword)) {
            return;
        }

        if (!$this->passwordStrengthEstimatorModel->validate($userPassword)) {
            throw new WeakPasswordException();
        }
    }
}
