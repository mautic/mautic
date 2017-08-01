<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Joomla\Http\Http;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event as Events;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\WebhookBundle\WebhookEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var Http
     */
    protected $connector;

    /**
     * CampaignSubscriber constructor.
     *
     * @param Http $connector
     */
    public function __construct(Http $connector)
    {
        $this->connector = $connector;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD         => ['onCampaignBuild', 0],
            WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignExecutionEvent $event
     *
     * @return CampaignExecutionEvent
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        if (!$event->checkContext('campaign.sendwebhook')) {
            return;
        }
        $lead   = $event->getLead();
        $config = $event->getConfig();
        try {
            $url    = $config['url'];
            $method = $config['method'];
            $data   = !empty($config['additional_data']['list']) ? $config['additional_data']['list'] : '';
            $data   = array_flip(AbstractFormFieldHelper::parseList($data));
            // replace contacts tokens
            foreach ($data as $key => $value) {
                $data[$key] = urlencode(TokenHelper::findLeadTokens($value, $lead->getProfileFields(), true));
            }
            $headers = !empty($config['headers']['list']) ? $config['headers']['list'] : '';
            $headers = array_flip(AbstractFormFieldHelper::parseList($headers));
            foreach ($headers as $key => $value) {
                $headers[$key] = urlencode(TokenHelper::findLeadTokens($value, $lead->getProfileFields(), true));
            }
            $timeout = $config['timeout'];

            if (in_array($method, ['get', 'trace'])) {
                $response = $this->connector->$method(
                    $url.(parse_url($url, PHP_URL_QUERY) ? '&' : '?').http_build_query($data),
                    $headers,
                    $timeout
                );
            } elseif (in_array($method, ['post', 'put', 'patch'])) {
                $response = $this->connector->$method(
                    $url,
                    $data,
                    $headers,
                    $timeout
                );
            } elseif ($method == 'delete') {
                $response = $this->connector->$method(
                    $url,
                    $headers,
                    $timeout,
                    $data
                );
            }
            if (in_array($response->code, [200, 201])) {
                return $event->setResult(true);
            }
        } catch (\Exception $e) {
            return $event->setFailed($e->getMessage());
        }

        return $event->setFailed($this->translator->trans('Error code').': '.$response->code);
    }

    /**
     * Add event triggers and actions.
     *
     * @param Events\CampaignBuilderEvent $event
     */
    public function onCampaignBuild(Events\CampaignBuilderEvent $event)
    {
        //Add action to remote url call
        $sendWebhookAction = [
            'label'       => 'mautic.webhook.event.sendwebhook',
            'description' => 'mautic.webhook.event.sendwebhook_desc',
            'formType'    => 'campaignevent_sendwebhook',
            'eventName'   => WebhookEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];
        $event->addAction('campaign.sendwebhook', $sendWebhookAction);
    }
}
