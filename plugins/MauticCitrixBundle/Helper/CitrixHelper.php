<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticCitrixBundle\Api\GotoassistApi;
use MauticPlugin\MauticCitrixBundle\Api\GotomeetingApi;
use MauticPlugin\MauticCitrixBundle\Api\GototrainingApi;
use MauticPlugin\MauticCitrixBundle\Api\GotowebinarApi;
use Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Mautic\PluginBundle\Helper\IntegrationHelper;

class CitrixHelper
{
    /** @var Container $container */
    private static $container;

    /**
     * @param Container $container
     */
    public static function init(Container $container)
    {
        self::$container = $container;
    }

    /**
     * @return Container
     */
    public static function getContainer()
    {
        return self::$container;
    }

    /**
     * Get the API helper.
     *
     * @return GotomeetingApi
     */
    public static function getG2mApi()
    {
        static $g2mapi;
        if (null === $g2mapi) {
            $class = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotomeetingApi';
            $g2mapi = new $class(self::getIntegration('Gotomeeting'));
        }

        return $g2mapi;
    }

    /**
     * Get the API helper.
     *
     * @return GotowebinarApi
     */
    public static function getG2wApi()
    {
        static $g2wapi;
        if (null === $g2wapi) {
            $class = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotowebinarApi';
            $g2wapi = new $class(self::getIntegration('Gotowebinar'));
        }

        return $g2wapi;
    }

    /**
     * Get the API helper.
     *
     * @return GototrainingApi
     */
    public static function getG2tApi()
    {
        static $g2tapi;
        if (null === $g2tapi) {
            $class = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GototrainingApi';
            $g2tapi = new $class(self::getIntegration('Gototraining'));
        }

        return $g2tapi;
    }

    /**
     * Get the API helper.
     *
     * @return GotoassistApi
     */
    public static function getG2aApi()
    {
        static $g2aapi;
        if (null === $g2aapi) {
            $class = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotoassistApi';
            $g2aapi = new $class(self::getIntegration('Gotoassist'));
        }

        return $g2aapi;
    }

    /**
     * @param $msg
     * @param string $level
     */
    public static function log($msg, $level = 'error')
    {
        /** @var Logger $logger */
        static $logger;

        try {
            if (null === $logger) {
                $logger = self::$container->get('monolog.logger.mautic');
            }
            $logger->log($level, $msg);
        } catch (\Exception $ex) {

        }
    }

    /**
     * @param array|string $results
     * @param $key
     * @param $value
     * @return \Generator
     */
    public static function getKeyPairs($results, $key, $value)
    {
        if (!(array)$results) {
            $results = [$results];
        }
        /** @var array $results */
        foreach ($results as $result) {
            if (array_key_exists($key, $result) && array_key_exists($value, $result)) {
                yield $result[$key] => $result[$value];
            }
        }
    }

    /**
     * @param array|string $results
     * @param bool $showAll
     * @return \Generator
     */
    public static function getAssistPairs($results, $showAll = true)
    {
        $sessions = $results['sessions'];
        /** @var array $sessions */
        foreach ($sessions as $session) {
            if ($showAll || !in_array($session['status'], ['complete', 'abandoned'], true)) {
                yield $session['sessionId'] => sprintf('%s (%s)', $session['sessionId'], $session['status']);
            }
        }

    }

    /**
     * @param $listType string Can be one of 'webinars', 'meetings', 'trainings' or 'assists'
     * @return array
     */
    public static function getCitrixChoices($listType)
    {
        try {
            // Check if integration is enabled
            if (!self::isAuthorized(self::listToIntegration($listType))) {
                throw new \AuthenticationException('You are not authorized to view '.$listType);
            }

            if ('webinars' === $listType) {
                $results = self::getG2wApi()->request('upcomingWebinars');

                return iterator_to_array(self::getKeyPairs($results, 'webinarID', 'subject'));
            } else {
                if ('meetings' === $listType) {
                    $results = self::getG2mApi()->request('upcomingMeetings');

                    return iterator_to_array(self::getKeyPairs($results, 'meetingId', 'subject'));
                } else {
                    if ('trainings' === $listType) {
                        $results = self::getG2tApi()->request('trainings');

                        return iterator_to_array(self::getKeyPairs($results, 'trainingId', 'name'));
                    } else {
                        if ('assists' === $listType) {
                            $params = [
                                'fromTime' => preg_filter(
                                    '/^(.+)[\+\-].+$/',
                                    '$1Z',
                                    date('c', strtotime('-1 month', time()))
                                ),
                                'toTime' => preg_filter('/^(.+)[\+\-].+$/', '$1Z', date('c')),
                            ];
                            $results = self::getG2aApi()->request('sessions', $params);
                            if ((array)$results && array_key_exists('sessions', $results)) {
                                return iterator_to_array(self::getAssistPairs($results));
                            }
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            self::log($ex->getMessage());
        }

        return [];
    }

    /**
     * @param $integration string
     * @return boolean
     */
    private static function isAuthorized($integration)
    {
        $myIntegration = self::getIntegration($integration);

        return $myIntegration && $myIntegration->getIntegrationSettings()->getIsPublished();
    }

    /**
     * @param $integration
     * @return AbstractIntegration
     */
    private static function getIntegration($integration)
    {
        try {
            /** @var IntegrationHelper $integrationHelper */
            $integrationHelper = self::$container->get('mautic.helper.integration');

            return $integrationHelper->getIntegrationObject($integration);
        } catch (\Exception $e) {

        }

        return null;
    }

    /**
     * @param $listType
     * @return mixed
     */
    private static function listToIntegration($listType)
    {
        $integrations = [
            'webinars' => 'Gotowebinar',
            'meetings' => 'Gotomeeting',
            'trainings' => 'Gototraining',
            'assists' => 'Gotoassist',
        ];

        return $integrations[$listType];
    }

}
