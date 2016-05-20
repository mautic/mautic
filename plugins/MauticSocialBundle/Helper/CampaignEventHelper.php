<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use MauticPlugin\MauticSocialBundle\Model\SocialEventLogModel;

/**
 * Class CampaignEventHelper
 *
 * @package MauticPlugin\MauticSocialBundle\Helper\CampaignEventHelper
 */
class CampaignEventHelper
{
    static $factory;

    /**
     * @param MauticFactory $factory
     * @param               $lead
     * @param               $event
     *
     * @return bool
     */
    public static function sendTweetAction (MauticFactory $factory, $lead, $event)
    {
        static::$factory = $factory;

        $tweetSent = false;

        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $factory->getHelper('integration');

        /** @var \MauticPlugin\MauticSocialBundle\Integration\TwitterIntegration $twitterIntegration */
        $twitterIntegration = $integrationHelper->getIntegrationObject('Twitter');

        $tweetText = $event['properties']['tweet_text'];

        $tweetText = CampaignEventHelper::parseTweetText($tweetText, $lead);

        $tweetUrl = $twitterIntegration->getApiUrl('statuses/update');

        $status = array('status' => $tweetText);

        // fire the tweet
        $sendTweet = $twitterIntegration->makeRequest($tweetUrl, $status, 'POST', array('append_callback' => false));

        $tweetId = '';

        // verify the tweet was sent by checking for a tweet id
        if (is_array($sendTweet) && array_key_exists('id_str', $sendTweet))
        {
            $tweetSent = true;
            $tweetId = $sendTweet['id_str'];
        }

        //CampaignEventHelper::updateSocialLog($event, $lead, $tweetId, $tweetText, $tweetSent);

        return $tweetSent;
    }

    /*
     * PreParse the twitter message and replace placeholders with values.
     *
     * @param $tweet the tweet messsage
     * @param $lead the lead entity
     */
    protected static function parseTweetText($tweet, $lead)
    {
        /* @var \Mautic\LeadBundle\Entity\Lead $lead */
        $leadFields = $lead->getFields();

        // check for twitter handle on the lead
        if (isset($leadFields['social']['twitter']['value'])) {
            $tweetHandle = $leadFields['social']['twitter']['value'];
        }

        // replace tweet text with the handle and pre-pend with at symbol
        if ((strpos($tweet, '{twitter_handle}') !== false) && isset($tweetHandle)) {
            $tweet = str_ireplace('{twitter_handle}', '@' . $tweetHandle , $tweet);
        }

        $clickthrough['campaign'] = $lead->getId();

        // replace the asset link
        $tweet = self::replaceAssets($tweet, $clickthrough);

        // replace the page url
        $tweet = self::replacePage($tweet, $clickthrough);

        return $tweet;
    }

    /**
     * @param $tweet
     * @param $clickthrough
     *
     * @return mixed
     */
    protected static function replaceAssets($tweet, $clickthrough)
    {
        $factory = static::$factory;

        // replace asset links
        $assetRegex = '/{assetlink=(.*?)}/';

        // check to see if asset links are in the tweet
        preg_match_all($assetRegex, $tweet, $matches);
        if (!empty($matches[1])) {

            /** @var \Mautic\AssetBundle\Model\AssetModel $model */
            $assetModel = $factory->getModel('asset');

            foreach ($matches[1] as $match) {
                if (empty($assets[$match])) {
                    $assets[$match] = $assetModel->getEntity($match);
                }

                $url  = ($assets[$match] !== null) ? $assetModel->generateUrl($assets[$match], true, $clickthrough) : '';

                // encode the url for tweeting
                urlencode($url);

                $tweet = str_ireplace('{assetlink=' . $match . '}', $url, $tweet);
            }
        }

        return $tweet;
    }

    /**
     * @param $tweet
     * @param $clickthrough
     *
     * @return mixed
     */
    protected static function replacePage($tweet, $clickthrough)
    {
        $factory = static::$factory;

        $pagelinkRegex = '/{pagelink=(.*?)}/';

        preg_match_all($pagelinkRegex, $tweet, $matches);
        if (!empty($matches[1])) {

            /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
            $pageModel = $factory->getModel('page');

            foreach ($matches[1] as $match) {
                if (empty($pages[$match])) {
                    $pages[$match] = $pageModel->getEntity($match);
                }

                $url     = ($pages[$match] !== null) ? $pageModel->generateUrl($pages[$match], true, $clickthrough) : '';

                // encode the url for tweeting
                urlencode($url);

                $tweet = str_ireplace('{pagelink=' . $match . '}', $url, $tweet);
            }
        }

        return $tweet;
    }

    /*
     * takes an array of query params for twitter and gives a list back.
     *
     * URL Encoding done in makeRequest()
     */
    protected static function buildTwitterSearchQuery(Array $query)
    {
        $queryString = implode(' ', $query);

        return $queryString;
    }

    /*
     *
     */
    protected static function updateSocialLog($event, $lead, $networkMessageId, $message, $sent)
    {
        /* @var \MauticPlugin\MauticSocialBundle\Model\SocialEventLogModel $logModel */
        $logModel = new SocialEventLogModel(static::$factory);

        /* @var \MauticPlugin\MauticSocialBundle\Entity\SocialEventLog $logEntity */
        $logEntity = $logModel->getEntity();


        $now = new \DateTime();

        $logEntity->setLead($lead);
        $logEntity->setDateTriggered($now);
        $logEntity->setMessageSent($message);
        $logEntity->setEventId($event['id']);
        $logEntity->setEventType($event['type']);
        $logEntity->setNetworkType('twitter');
        $logEntity->setNetworkId($networkMessageId);
        $logEntity->setNetworkResponse($sent);

        $logModel->saveEntity($logEntity);
    }
}