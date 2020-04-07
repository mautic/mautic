<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Helper;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticSocialBundle\Model\TweetModel;

/**
 * Class CampaignEventHelper.
 */
class CampaignEventHelper
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var TweetModel
     */
    protected $tweetModel;

    /**
     * @var array
     */
    protected $clickthrough = [];

    /**
     * CampaignEventHelper constructor.
     */
    public function __construct(
        IntegrationHelper $integrationHelper,
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        TweetModel $tweetModel
    ) {
        $this->integrationHelper = $integrationHelper;
        $this->trackableModel    = $trackableModel;
        $this->pageTokenHelper   = $pageTokenHelper;
        $this->assetTokenHelper  = $assetTokenHelper;
        $this->tweetModel        = $tweetModel;
    }

    /**
     * @return array|false
     */
    public function sendTweetAction(Lead $lead, array $event)
    {
        $tweetSent   = false;
        $tweetEntity = $this->tweetModel->getEntity($event['channelId']);

        if (!$tweetEntity) {
            return ['failed' => 1, 'response' => 'Tweet entity '.$event['channelId'].' not found'];
        }

        /** @var \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration $twitterIntegration */
        $twitterIntegration = $this->integrationHelper->getIntegrationObject('Twitter');

        // Setup clickthrough for URLs in tweet
        $this->clickthrough = [
            'source' => ['campaign', $event['campaign']['id']],
        ];

        $leadArray = $lead->getProfileFields();
        if (empty($leadArray['twitter'])) {
            return false;
        }

        $tweetText = $tweetEntity->getText();
        $tweetText = $this->parseTweetText($tweetText, $leadArray, $tweetEntity->getId());
        $tweetUrl  = $twitterIntegration->getApiUrl('statuses/update');
        $status    = ['status' => $tweetText];

        // fire the tweet
        $sendResponse = $twitterIntegration->makeRequest($tweetUrl, $status, 'POST', ['append_callback' => false]);

        // verify the tweet was sent by checking for a tweet id
        if (is_array($sendResponse) && array_key_exists('id_str', $sendResponse)) {
            $tweetSent = true;
        }

        if ($tweetSent) {
            $this->tweetModel->registerSend($tweetEntity, $lead, $sendResponse, 'campaign.event', $event['id']);

            return ['timeline' => $tweetText, 'response' => $sendResponse];
        }

        $response = ['failed' => 1, 'response' => $sendResponse];
        if (!empty($sendResponse['error']['message'])) {
            $response['reason'] = $sendResponse['error']['message'];
        }

        return $response;
    }

    /**
     * PreParse the twitter message and replace placeholders with values.
     *
     * @param string $text
     * @param array  $lead
     * @param int    $channelId
     *
     * @return string
     */
    protected function parseTweetText($text, $lead, $channelId = -1)
    {
        $tweetHandle = $lead['twitter'];
        $tokens      = [
            '{twitter_handle}' => (false !== strpos($tweetHandle, '@')) ? $tweetHandle : "@$tweetHandle",
        ];

        $tokens = array_merge(
            $tokens,
            TokenHelper::findLeadTokens($text, $lead),
            $this->pageTokenHelper->findPageTokens($text, $this->clickthrough),
            $this->assetTokenHelper->findAssetTokens($text, $this->clickthrough)
        );

        list($text, $trackables) = $this->trackableModel->parseContentForTrackables(
            $text,
            $tokens,
            'social_twitter',
            $channelId
        );

        /**
         * @var string
         * @var Trackable $trackable
         */
        foreach ($trackables as $token => $trackable) {
            $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $this->clickthrough);
        }

        return str_replace(array_keys($tokens), array_values($tokens), $text);
    }
}
