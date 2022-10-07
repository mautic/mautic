# Backwards compatibility breaking changes
*   Platform Requirements
    *   Minimal PHP version was increased from 7.4 to 8.0 and 8.1.
*   Commands
    * The command `bin/console mautic:segments:update` will no longer update the campaign members but only the segment members. Use also command `bin/console mautic:campaigns:update` to update the campaign members if you haven't already. Both commands are recommended from Mautic 1.
    * Command `Mautic\LeadBundle\Command\CheckQueryBuildersCommand` and the methods it use:
        * `Mautic\LeadBundle\Model\ListModel::getVersionNew()`
        * `Mautic\LeadBundle\Model\ListModel::getVersionOld()`
*   Other
    * `Mautic\UserBundle\Security\Firewall\AuthenticationListener::class` no longer implements the deprecated `Symfony\Component\Security\Http\Firewall\ListenerInterface` and was made final. The `public function handle(GetResponseEvent $event)` method was changed to `public function __invoke(RequestEvent $event): void` to support Symfony 5.
    * Mautic\IntegrationsBundle\Configuration\PluginConfiguration removed - we don't use it
    * Mautic\CoreBundle\Templating\Helper\ExceptionHelper removed - we don't use it
    * The User entity no longer implements Symfony\Component\Security\Core\User\AdvancedUserInterface as it was removed from Symfony 5. These methods required by the interface were also removed:
        * Mautic\UserBundle\Entity\User::isAccountNonExpired()
        * Mautic\UserBundle\Entity\User::isAccountNonLocked()
        * Mautic\UserBundle\Entity\User::isCredentialsNonExpired()
        * Mautic\UserBundle\Entity\User::isEnabled()
    * Two French regions were updates based on ISO_3166-2 (Val-d\'Oise, La Réunion). If you use it in API, please change values to Val d\'Oise or Réunion
    * `AbstractMauticTestCase::loadFixtures` and `AbstractMauticTestCase::loadFixtureFiles` now accept only two arguments: `array $fixtures` and `bool $append`. If you need to use old parameters - refer to the documentation of `LiipTestFixturesBundle`
