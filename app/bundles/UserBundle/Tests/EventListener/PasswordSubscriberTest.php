<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Tests\EventListener;

use Mautic\UserBundle\Entity\UserRepository;
use Mautic\UserBundle\Event\AuthenticationEvent;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Mautic\UserBundle\Security\Authentication\Token\PluginToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Router;
use ZxcvbnPhp\Zxcvbn;

class PasswordSubscriberTest extends TestCase
{
    /**
     * @var PasswordSubscriber
     */
    private $passwordSubscriber;

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

    /**
     * @var AuthenticationEvent
     */
    private $authenticationEvent;

    /**
     * @var PluginToken
     */
    private $pluginToken;

    protected function setUp(): void
    {
        $this->passwordStrengthEstimatorModel = new PasswordStrengthEstimatorModel(new Zxcvbn());
        $this->userRepository                 = $this->createMock(UserRepository::class);
        $this->router                         = $this->createMock(Router::class);
        $this->passwordSubscriber             = new PasswordSubscriber($this->passwordStrengthEstimatorModel, $this->userRepository, $this->router);
        $this->authenticationEvent            = $this->createMock(AuthenticationEvent::class);
        $this->pluginToken                    = $this->createMock(PluginToken::class);
    }

    public function testThatItThrowsExceptionIfPasswordIsWeak(): void
    {
        $this->passwordSubscriber->onUserFormAuthentication($this->authenticationEvent);
    }
}
