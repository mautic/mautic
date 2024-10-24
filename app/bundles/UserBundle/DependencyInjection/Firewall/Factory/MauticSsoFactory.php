<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection\Firewall\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class MauticSsoFactory extends AbstractFactory implements AuthenticatorFactoryInterface
{
    /**
     * Before form_login, otherwise the \Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator::supports
     * of form_login will return true, and the authentication will be finished with an error.
     * SSO Authenticator will be executed only if it has same options as form_login + request
     * will have 'integration_parameter' in the Request.
     */
    public const PRIORITY = -25;

    public function __construct()
    {
        $this->addOption('username_parameter', '_username');
        $this->addOption('password_parameter', '_password');
        $this->addOption('integration_parameter', 'integration');
        $this->addOption('csrf_parameter', '_csrf_token');
        $this->addOption('csrf_token_id', 'authenticate');
        $this->addOption('enable_csrf', true);
        $this->addOption('post_only', true);
        $this->addOption('form_only', false);
        $this->addOption('login_path', '/s/login');
        $this->addOption('check_path', '/s/login_check');
    }

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticatorId = 'security.authenticator.mautic_sso.'.$firewallName;

        // use same auth handlers as in login_form
        $authenticationSuccessHandlerId = $this->getSuccessHandlerId($firewallName);
        $formLoginSuccessHandlerId      = str_replace('mautic_sso', 'form_login', $authenticationSuccessHandlerId);
        $authenticationFailureHandlerId = $this->getFailureHandlerId($firewallName);
        $formLoginFailureHandlerId      = str_replace('mautic_sso', 'form_login', $authenticationFailureHandlerId);

        $authenticator = $container
            ->setDefinition($authenticatorId, new ChildDefinition('security.authenticator.mautic_sso'))
            ->replaceArgument('$options', []) // Options will be replaced in \Mautic\UserBundle\DependencyInjection\Compiler\SsoAuthenticatorPass
            ->replaceArgument('$userProvider', new Reference($userProviderId))
            ->replaceArgument('$successHandler', new Reference($formLoginSuccessHandlerId))
            ->replaceArgument('$failureHandler', new Reference($formLoginFailureHandlerId));

        $container->setDefinition($authenticatorId, $authenticator);

        return $authenticatorId;
    }

    public function getKey(): string
    {
        return 'mautic-sso';
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getPosition(): string
    {
        return 'form';
    }

    /**
     * @param array<mixed> $config
     */
    protected function createAuthProvider(ContainerBuilder $container, string $id, array $config, string $userProviderId): string
    {
        throw new \Exception('The old authentication system is not supported with mautic-sso.');
    }

    protected function getListenerId(): string
    {
        throw new \Exception('The old authentication system is not supported with mautic-sso.');
    }
}
