<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 300 local event subscribers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_EVENT_SUBSCRIBER_COUNT = 297;

    /**
     * Local event subscribers per hand-picked event, to catch a subscriber that silently stops listening.
     *
     * @var array<string, string[]>
     */
    private const EXPECTED_EVENT_SUBSCRIBER_CLASSES = [
        \Symfony\Component\Security\Http\Event\LogoutEvent::class => [
            \Mautic\UserBundle\EventListener\LogoutListener::class,
        ],
        \Symfony\Component\Security\Http\Event\CheckPassportEvent::class => [
            \Mautic\UserBundle\EventListener\ApiUserSubscriber::class,
            \Mautic\UserBundle\EventListener\PasswordStrengthSubscriber::class,
            \Mautic\UserBundle\EventListener\PasswordSubscriber::class,
        ],
        \Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent::class => [
            \Mautic\UserBundle\EventListener\ApiUserSubscriber::class,
        ],
        'kernel.request' => [
            \MauticPlugin\MauticFocusBundle\EventListener\FocusSubscriber::class,
            \Mautic\ApiBundle\EventListener\ApiSubscriber::class,
            \Mautic\CoreBundle\EventListener\AssetsSubscriber::class,
            \Mautic\CoreBundle\EventListener\EnvironmentSubscriber::class,
            \Mautic\CoreBundle\EventListener\ErrorHandlingListener::class,
            \Mautic\CoreBundle\EventListener\RequestSubscriber::class,
            \Mautic\CoreBundle\EventListener\RouterSubscriber::class,
            \Mautic\UserBundle\Controller\SecurityController::class,
        ],
        'kernel.response' => [
            \Mautic\ApiBundle\EventListener\ApiSubscriber::class,
            \Mautic\CoreBundle\EventListener\ExceptionListener::class,
            \Mautic\CoreBundle\Helper\CookieHelper::class,
        ],
        'kernel.exception' => [
            \Mautic\CoreBundle\EventListener\ExceptionListener::class,
        ],
        'kernel.controller' => [
            \Mautic\IntegrationsBundle\EventListener\ControllerSubscriber::class,
        ],
        \FOS\OAuthServerBundle\Event\OAuthEvent::PRE_AUTHORIZATION_PROCESS => [
            \Mautic\ApiBundle\EventListener\PreAuthorizationEventListener::class,
        ],
        \FOS\OAuthServerBundle\Event\OAuthEvent::POST_AUTHORIZATION_PROCESS => [
            \Mautic\ApiBundle\EventListener\PreAuthorizationEventListener::class,
        ],
        'security.interactive_login' => [
            \Mautic\CoreBundle\EventListener\CoreSubscriber::class,
        ],
        'mautic.campaign_on_build' => [
            \MauticPlugin\MauticFocusBundle\EventListener\CampaignSubscriber::class,
            \MauticPlugin\MauticSocialBundle\EventListener\CampaignSubscriber::class,
            \Mautic\AssetBundle\EventListener\CampaignSubscriber::class,
            \Mautic\CampaignBundle\EventListener\CampaignActionChangeMembershipSubscriber::class,
            \Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber::class,
            \Mautic\ChannelBundle\EventListener\CampaignSubscriber::class,
            \Mautic\DynamicContentBundle\EventListener\CampaignSubscriber::class,
            \Mautic\EmailBundle\EventListener\CampaignConditionSubscriber::class,
            \Mautic\EmailBundle\EventListener\CampaignSubscriber::class,
            \Mautic\FormBundle\EventListener\CampaignSubscriber::class,
            \Mautic\LeadBundle\EventListener\CampaignActionDNCSubscriber::class,
            \Mautic\LeadBundle\EventListener\CampaignActionDeleteContactSubscriber::class,
            \Mautic\LeadBundle\EventListener\CampaignSubscriber::class,
            \Mautic\NotificationBundle\EventListener\CampaignConditionSubscriber::class,
            \Mautic\NotificationBundle\EventListener\CampaignSubscriber::class,
            \Mautic\PageBundle\EventListener\CampaignSubscriber::class,
            \Mautic\PluginBundle\EventListener\CampaignSubscriber::class,
            \Mautic\SmsBundle\EventListener\CampaignReplySubscriber::class,
            \Mautic\SmsBundle\EventListener\CampaignSendSubscriber::class,
            \Mautic\StageBundle\EventListener\CampaignSubscriber::class,
            \Mautic\WebhookBundle\EventListener\CampaignSubscriber::class,
        ],
        'mautic.report_on_build' => [
            \MauticPlugin\MauticFocusBundle\EventListener\FocusSubscriber::class,
            \MauticPlugin\MauticFocusBundle\EventListener\ReportSubscriber::class,
            \Mautic\AssetBundle\EventListener\ReportSubscriber::class,
            \Mautic\CampaignBundle\EventListener\ReportSubscriber::class,
            \Mautic\ChannelBundle\EventListener\ReportSubscriber::class,
            \Mautic\CoreBundle\EventListener\ReportSubscriber::class,
            \Mautic\EmailBundle\EventListener\ReportSubscriber::class,
            \Mautic\FormBundle\EventListener\ReportSubscriber::class,
            \Mautic\LeadBundle\EventListener\ReportDNCSubscriber::class,
            \Mautic\LeadBundle\EventListener\ReportDevicesSubscriber::class,
            \Mautic\LeadBundle\EventListener\ReportSubscriber::class,
            \Mautic\LeadBundle\EventListener\ReportUtmTagSubscriber::class,
            \Mautic\LeadBundle\EventListener\SegmentLogReportSubscriber::class,
            \Mautic\LeadBundle\EventListener\SegmentReportSubscriber::class,
            \Mautic\NotificationBundle\EventListener\ReportSubscriber::class,
            \Mautic\PageBundle\EventListener\ReportSubscriber::class,
            \Mautic\PointBundle\EventListener\ReportSubscriber::class,
        ],
        'mautic.config_on_generate' => [
            \MauticPlugin\MauticSocialBundle\EventListener\ConfigSubscriber::class,
            \Mautic\ApiBundle\EventListener\ConfigSubscriber::class,
            \Mautic\AssetBundle\EventListener\ConfigSubscriber::class,
            \Mautic\CampaignBundle\EventListener\ConfigSubscriber::class,
            \Mautic\CoreBundle\EventListener\ConfigSubscriber::class,
            \Mautic\CoreBundle\EventListener\ConfigThemeSubscriber::class,
            \Mautic\EmailBundle\EventListener\ConfigSubscriber::class,
            \Mautic\FormBundle\EventListener\ConfigSubscriber::class,
            \Mautic\LeadBundle\EventListener\ConfigSubscriber::class,
            \Mautic\MessengerBundle\EventListener\ConfigSubscriber::class,
            \Mautic\NotificationBundle\EventListener\ConfigSubscriber::class,
            \Mautic\PageBundle\EventListener\ConfigSubscriber::class,
            \Mautic\ReportBundle\EventListener\ConfigSubscriber::class,
            \Mautic\SmsBundle\EventListener\ConfigSubscriber::class,
            \Mautic\UserBundle\EventListener\ConfigSubscriber::class,
            \Mautic\WebhookBundle\EventListener\ConfigSubscriber::class,
        ],
        'mautic.config_pre_save' => [
            \MauticPlugin\MauticSocialBundle\EventListener\ConfigSubscriber::class,
            \Mautic\ApiBundle\EventListener\ConfigSubscriber::class,
            \Mautic\CampaignBundle\EventListener\ConfigSubscriber::class,
            \Mautic\CoreBundle\EventListener\ConfigSubscriber::class,
            \Mautic\EmailBundle\EventListener\ConfigSubscriber::class,
            \Mautic\PageBundle\EventListener\ConfigSubscriber::class,
            \Mautic\UserBundle\EventListener\ConfigSubscriber::class,
        ],
        'mautic.email_on_send' => [
            \Mautic\AssetBundle\EventListener\BuilderSubscriber::class,
            \Mautic\EmailBundle\EventListener\BuilderSubscriber::class,
            \Mautic\EmailBundle\EventListener\DateTimeTokenSubscriber::class,
            \Mautic\EmailBundle\EventListener\EmailSubscriber::class,
            \Mautic\EmailBundle\EventListener\PointSubscriber::class,
            \Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber::class,
            \Mautic\EmailBundle\EventListener\TokenSubscriber::class,
            \Mautic\EmailBundle\EventListener\WebhookSubscriber::class,
            \Mautic\IntegrationsBundle\EventListener\EmailSubscriber::class,
            \Mautic\LeadBundle\EventListener\EmailSubscriber::class,
            \Mautic\LeadBundle\EventListener\OwnerSubscriber::class,
            \Mautic\PageBundle\EventListener\BuilderSubscriber::class,
        ],
        'mautic.form_on_build' => [
            \MauticPlugin\MauticSocialBundle\EventListener\FormSubscriber::class,
            \Mautic\AssetBundle\EventListener\FormSubscriber::class,
            \Mautic\EmailBundle\EventListener\FormSubscriber::class,
            \Mautic\FormBundle\EventListener\FormSubscriber::class,
            \Mautic\FormBundle\EventListener\FormValidationSubscriber::class,
            \Mautic\LeadBundle\EventListener\FormSubscriber::class,
            \Mautic\PluginBundle\EventListener\FormSubscriber::class,
        ],
        'mautic.lead_post_save' => [
            \Mautic\IntegrationsBundle\EventListener\LeadSubscriber::class,
            \Mautic\LeadBundle\EventListener\LeadSubscriber::class,
            \Mautic\LeadBundle\EventListener\WebhookSubscriber::class,
            \Mautic\PluginBundle\EventListener\LeadSubscriber::class,
            \Mautic\PointBundle\EventListener\LeadSubscriber::class,
        ],
    ];

    public function testAllEventSubscribersCanBeCreated(): void
    {
        $this->assertGreaterThanOrEqual(self::MINIMAL_EVENT_SUBSCRIBER_COUNT, count($this->resolveEventSubscribers()));
    }

    public function testEventSubscriberClassesPerEvent(): void
    {
        $eventSubscriberClasses = [];

        foreach ($this->resolveEventSubscribers() as $eventSubscriber) {
            foreach (array_keys($eventSubscriber::getSubscribedEvents()) as $eventName) {
                $eventSubscriberClasses[$eventName][] = $eventSubscriber::class;
            }
        }

        foreach (self::EXPECTED_EVENT_SUBSCRIBER_CLASSES as $eventName => $expectedClasses) {
            $currentClasses = $eventSubscriberClasses[$eventName] ?? [];
            sort($currentClasses);

            $this->assertSame(
                $expectedClasses,
                $currentClasses,
                sprintf('Unexpected local event subscribers for the "%s" event', $eventName)
            );
        }
    }

    /**
     * @return array<int, EventSubscriberInterface>
     */
    private function resolveEventSubscribers(): array
    {
        return array_filter(
            $this->createAllServices(),
            fn (object $service): bool => $service instanceof EventSubscriberInterface && $this->isLocalService($service)
        );
    }
}
