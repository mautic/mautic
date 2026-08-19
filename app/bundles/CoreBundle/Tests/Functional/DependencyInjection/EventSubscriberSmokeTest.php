<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use FOS\OAuthServerBundle\Event\OAuthEvent;
use MauticPlugin\MauticFocusBundle\EventListener\FocusSubscriber;
use Mautic\ApiBundle\EventListener\ApiSubscriber;
use Mautic\ApiBundle\EventListener\PreAuthorizationEventListener;
use Mautic\CampaignBundle\EventListener\CampaignActionChangeMembershipSubscriber;
use Mautic\CampaignBundle\EventListener\CampaignActionJumpToEventSubscriber;
use Mautic\CoreBundle\EventListener\AssetsSubscriber;
use Mautic\CoreBundle\EventListener\ConfigThemeSubscriber;
use Mautic\CoreBundle\EventListener\CoreSubscriber;
use Mautic\CoreBundle\EventListener\EnvironmentSubscriber;
use Mautic\CoreBundle\EventListener\ErrorHandlingListener;
use Mautic\CoreBundle\EventListener\ExceptionListener;
use Mautic\CoreBundle\EventListener\RequestSubscriber;
use Mautic\CoreBundle\EventListener\RouterSubscriber;
use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\EmailBundle\EventListener\DateTimeTokenSubscriber;
use Mautic\EmailBundle\EventListener\PointSubscriber;
use Mautic\EmailBundle\EventListener\ProcessUnsubscribeSubscriber;
use Mautic\EmailBundle\EventListener\TokenSubscriber;
use Mautic\FormBundle\EventListener\FormValidationSubscriber;
use Mautic\IntegrationsBundle\EventListener\ControllerSubscriber;
use Mautic\LeadBundle\EventListener\CampaignActionDNCSubscriber;
use Mautic\LeadBundle\EventListener\CampaignActionDeleteContactSubscriber;
use Mautic\LeadBundle\EventListener\OwnerSubscriber;
use Mautic\LeadBundle\EventListener\ReportDNCSubscriber;
use Mautic\LeadBundle\EventListener\ReportDevicesSubscriber;
use Mautic\LeadBundle\EventListener\ReportUtmTagSubscriber;
use Mautic\LeadBundle\EventListener\SegmentLogReportSubscriber;
use Mautic\LeadBundle\EventListener\SegmentReportSubscriber;
use Mautic\SmsBundle\EventListener\CampaignReplySubscriber;
use Mautic\SmsBundle\EventListener\CampaignSendSubscriber;
use Mautic\UserBundle\Controller\SecurityController;
use Mautic\UserBundle\EventListener\ApiUserSubscriber;
use Mautic\UserBundle\EventListener\LogoutListener;
use Mautic\UserBundle\EventListener\PasswordStrengthSubscriber;
use Mautic\UserBundle\EventListener\PasswordSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\AuthenticationTokenCreatedEvent;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class EventSubscriberSmokeTest extends AbstractContainerSmokeTestCase
{
    /**
     * There are 300 local event subscribers in the container, keep a small reserve for removed ones.
     */
    private const int MINIMAL_EVENT_SUBSCRIBER_COUNT = 297;

    /**
     * Local event subscribers per hand-picked event, to catch a subscriber that silently stops listening.
     *
     * @var array<string, string[]>
     */
    private const array EXPECTED_EVENT_SUBSCRIBER_CLASSES = [
        LogoutEvent::class => [
            LogoutListener::class,
        ],
        CheckPassportEvent::class => [
            ApiUserSubscriber::class,
            PasswordStrengthSubscriber::class,
            PasswordSubscriber::class,
        ],
        AuthenticationTokenCreatedEvent::class => [
            ApiUserSubscriber::class,
        ],
        'kernel.request' => [
            FocusSubscriber::class,
            ApiSubscriber::class,
            AssetsSubscriber::class,
            EnvironmentSubscriber::class,
            ErrorHandlingListener::class,
            RequestSubscriber::class,
            RouterSubscriber::class,
            SecurityController::class,
        ],
        'kernel.response' => [
            ApiSubscriber::class,
            ExceptionListener::class,
            CookieHelper::class,
        ],
        'kernel.exception' => [
            ExceptionListener::class,
        ],
        'kernel.controller' => [
            ControllerSubscriber::class,
        ],
        OAuthEvent::PRE_AUTHORIZATION_PROCESS => [
            PreAuthorizationEventListener::class,
        ],
        OAuthEvent::POST_AUTHORIZATION_PROCESS => [
            PreAuthorizationEventListener::class,
        ],
        'security.interactive_login' => [
            CoreSubscriber::class,
        ],
        'mautic.campaign_on_build' => [
            \MauticPlugin\MauticFocusBundle\EventListener\CampaignSubscriber::class,
            \MauticPlugin\MauticSocialBundle\EventListener\CampaignSubscriber::class,
            \Mautic\AssetBundle\EventListener\CampaignSubscriber::class,
            CampaignActionChangeMembershipSubscriber::class,
            CampaignActionJumpToEventSubscriber::class,
            \Mautic\ChannelBundle\EventListener\CampaignSubscriber::class,
            \Mautic\DynamicContentBundle\EventListener\CampaignSubscriber::class,
            \Mautic\EmailBundle\EventListener\CampaignConditionSubscriber::class,
            \Mautic\EmailBundle\EventListener\CampaignSubscriber::class,
            \Mautic\FormBundle\EventListener\CampaignSubscriber::class,
            CampaignActionDNCSubscriber::class,
            CampaignActionDeleteContactSubscriber::class,
            \Mautic\LeadBundle\EventListener\CampaignSubscriber::class,
            \Mautic\NotificationBundle\EventListener\CampaignConditionSubscriber::class,
            \Mautic\NotificationBundle\EventListener\CampaignSubscriber::class,
            \Mautic\PageBundle\EventListener\CampaignSubscriber::class,
            \Mautic\PluginBundle\EventListener\CampaignSubscriber::class,
            CampaignReplySubscriber::class,
            CampaignSendSubscriber::class,
            \Mautic\StageBundle\EventListener\CampaignSubscriber::class,
            \Mautic\WebhookBundle\EventListener\CampaignSubscriber::class,
        ],
        'mautic.report_on_build' => [
            FocusSubscriber::class,
            \MauticPlugin\MauticFocusBundle\EventListener\ReportSubscriber::class,
            \Mautic\AssetBundle\EventListener\ReportSubscriber::class,
            \Mautic\CampaignBundle\EventListener\ReportSubscriber::class,
            \Mautic\ChannelBundle\EventListener\ReportSubscriber::class,
            \Mautic\CoreBundle\EventListener\ReportSubscriber::class,
            \Mautic\EmailBundle\EventListener\ReportSubscriber::class,
            \Mautic\FormBundle\EventListener\ReportSubscriber::class,
            ReportDNCSubscriber::class,
            ReportDevicesSubscriber::class,
            \Mautic\LeadBundle\EventListener\ReportSubscriber::class,
            ReportUtmTagSubscriber::class,
            SegmentLogReportSubscriber::class,
            SegmentReportSubscriber::class,
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
            ConfigThemeSubscriber::class,
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
            DateTimeTokenSubscriber::class,
            \Mautic\EmailBundle\EventListener\EmailSubscriber::class,
            PointSubscriber::class,
            ProcessUnsubscribeSubscriber::class,
            TokenSubscriber::class,
            \Mautic\EmailBundle\EventListener\WebhookSubscriber::class,
            \Mautic\IntegrationsBundle\EventListener\EmailSubscriber::class,
            \Mautic\LeadBundle\EventListener\EmailSubscriber::class,
            OwnerSubscriber::class,
            \Mautic\PageBundle\EventListener\BuilderSubscriber::class,
        ],
        'mautic.form_on_build' => [
            \MauticPlugin\MauticSocialBundle\EventListener\FormSubscriber::class,
            \Mautic\AssetBundle\EventListener\FormSubscriber::class,
            \Mautic\EmailBundle\EventListener\FormSubscriber::class,
            \Mautic\FormBundle\EventListener\FormSubscriber::class,
            FormValidationSubscriber::class,
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
