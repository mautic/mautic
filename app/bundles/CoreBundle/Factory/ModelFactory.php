<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Model\MauticModelInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * @template M of object
 */
class ModelFactory
{
    /**
     * Tag applied (via autoconfiguration) to every MauticModelInterface service.
     */
    public const string TAG = 'mautic.model';

    public function __construct(
        #[AutowireLocator(self::TAG, defaultIndexMethod: 'getName')]
        private readonly ServiceLocator $container,
    ) {
    }

    /**
     * @return ($modelNameKey is 'asset' ? \Mautic\AssetBundle\Model\AssetModel
     *  : ($modelNameKey is 'campaign' ? \Mautic\CampaignBundle\Model\CampaignModel
     *  : ($modelNameKey is 'campaign.event' ? \Mautic\CampaignBundle\Model\EventModel
     *  : ($modelNameKey is 'campaign.event_log' ? \Mautic\CampaignBundle\Model\EventLogModel
     *  : ($modelNameKey is 'category' ? \Mautic\CategoryBundle\Model\CategoryModel
     *  : ($modelNameKey is 'channel.message' ? \Mautic\ChannelBundle\Model\MessageModel
     *  : ($modelNameKey is 'channel.queue' ? \Mautic\ChannelBundle\Model\MessageQueueModel
     *  : ($modelNameKey is 'core.auditlog' ? \Mautic\CoreBundle\Model\AuditLogModel
     *  : ($modelNameKey is 'core.notification' ? \Mautic\CoreBundle\Model\NotificationModel
     *  : ($modelNameKey is 'dashboard' ? \Mautic\DashboardBundle\Model\DashboardModel
     *  : ($modelNameKey is 'dynamicContent' ? \Mautic\DynamicContentBundle\Model\DynamicContentModel
     *  : ($modelNameKey is 'email' ? \Mautic\EmailBundle\Model\EmailModel
     *  : ($modelNameKey is 'focus' ? \MauticPlugin\MauticFocusBundle\Model\FocusModel
     *  : ($modelNameKey is 'form' ? \Mautic\FormBundle\Model\FormModel
     *  : ($modelNameKey is 'form.action' ? \Mautic\FormBundle\Model\ActionModel
     *  : ($modelNameKey is 'form.field' ? \Mautic\FormBundle\Model\FieldModel
     *  : ($modelNameKey is 'form.form' ? \Mautic\FormBundle\Model\FormModel
     *  : ($modelNameKey is 'form.submission' ? \Mautic\FormBundle\Model\SubmissionModel
     *  : ($modelNameKey is 'form.submission_result_loader' ? \Mautic\FormBundle\Model\SubmissionResultLoader
     *  : ($modelNameKey is 'lead' ? \Mautic\LeadBundle\Model\LeadModel
     *  : ($modelNameKey is 'lead.company' ? \Mautic\LeadBundle\Model\CompanyModel
     *  : ($modelNameKey is 'lead.device' ? \Mautic\LeadBundle\Model\DeviceModel
     *  : ($modelNameKey is 'lead.export_scheduler' ? \Mautic\LeadBundle\Model\ContactExportSchedulerModel
     *  : ($modelNameKey is 'lead.field' ? \Mautic\LeadBundle\Model\FieldModel
     *  : ($modelNameKey is 'lead.lead' ? \Mautic\LeadBundle\Model\LeadModel
     *  : ($modelNameKey is 'lead.list' ? \Mautic\LeadBundle\Model\ListModel
     *  : ($modelNameKey is 'lead.note' ? \Mautic\LeadBundle\Model\NoteModel
     *  : ($modelNameKey is 'lead.tag' ? \Mautic\LeadBundle\Model\TagModel
     *  : ($modelNameKey is 'notification' ? \Mautic\CoreBundle\Model\NotificationModel
     *  : ($modelNameKey is 'page' ? \Mautic\PageBundle\Model\PageModel
     *  : ($modelNameKey is 'page.page' ? \Mautic\PageBundle\Model\PageModel
     *  : ($modelNameKey is 'page.trackable' ? \Mautic\PageBundle\Model\TrackableModel
     *  : ($modelNameKey is 'plugin' ? \Mautic\PluginBundle\Model\PluginModel
     *  : ($modelNameKey is 'point' ? \Mautic\PointBundle\Model\PointModel
     *  : ($modelNameKey is 'point.insight' ? \Mautic\PointBundle\Model\InsightModel
     *  : ($modelNameKey is 'point.trigger' ? \Mautic\PointBundle\Model\TriggerModel
     *  : ($modelNameKey is 'point.triggerevent' ? \Mautic\PointBundle\Model\TriggerEventModel
     *  : ($modelNameKey is 'report' ? \Mautic\ReportBundle\Model\ReportModel
     *  : ($modelNameKey is 'sms' ? \Mautic\SmsBundle\Model\SmsModel
     *  : ($modelNameKey is 'social.monitoring' ? \MauticPlugin\MauticSocialBundle\Model\MonitoringModel
     *  : ($modelNameKey is 'social.postcount' ? \MauticPlugin\MauticSocialBundle\Model\PostCountModel
     *  : ($modelNameKey is 'social.tweet' ? \MauticPlugin\MauticSocialBundle\Model\TweetModel
     *  : ($modelNameKey is 'stage' ? \Mautic\StageBundle\Model\StageModel
     *  : ($modelNameKey is 'stage.stage' ? \Mautic\StageBundle\Model\StageModel
     *  : ($modelNameKey is 'tagmanager.tag' ? \MauticPlugin\MauticTagManagerBundle\Model\TagModel
     *  : ($modelNameKey is 'user' ? \Mautic\UserBundle\Model\UserModel
     *  : ($modelNameKey is 'user.role' ? \Mautic\UserBundle\Model\RoleModel
     *  : ($modelNameKey is 'user.user' ? \Mautic\UserBundle\Model\UserModel
     *  : ($modelNameKey is 'webhook' ? \Mautic\WebhookBundle\Model\WebhookModel
     *      : \Mautic\CoreBundle\Model\AbstractCommonModel<object>)))))))))))))))))))))))))))))))))))))))))))))))))
     */
    public function getModel(string $modelNameKey): MauticModelInterface
    {
        // Shortcut for models with the same name as the bundle, e.g. "lead" => "lead.lead"
        if (!str_contains($modelNameKey, '.')) {
            $modelNameKey = "{$modelNameKey}.{$modelNameKey}";
        }

        // Each model is registered in the locator under the key returned by its static getName() method.
        if ($this->container->has($modelNameKey)) {
            return $this->container->get($modelNameKey);
        }

        throw new \InvalidArgumentException($modelNameKey.' is not a registered model key.');
    }

    public function hasModel(string $modelNameKey): bool
    {
        try {
            $this->getModel($modelNameKey);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }
}
