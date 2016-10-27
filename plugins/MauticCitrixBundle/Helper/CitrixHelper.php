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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

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
            $logger->log($level, '**********************************************************************');
            $logger->log($level, $msg);
            $logger->log($level, '**********************************************************************');
        } catch (\Exception $ex) {
            // do nothing
        }
    }

    /**
     * @param array $results
     * @param $key
     * @param $value
     * @return \Generator
     */
    public static function getKeyPairs($results, $key, $value)
    {
        /** @var array $results */
        foreach ($results as $result) {
            if (array_key_exists($key, $result) && array_key_exists($value, $result)) {
                yield $result[$key] => $result[$value];
            }
        }
    }

    /**
     * @param array $sessions
     * @param bool $showAll Wether or not to show only active sessions
     * @return \Generator
     */
    public static function getAssistPairs($sessions, $showAll = false)
    {
        /** @var array $sessions */
        foreach ($sessions as $session) {
            if ($showAll || !in_array($session['status'], ['complete', 'abandoned'], true)) {
                yield $session['sessionId'] => sprintf('%s (%s)', $session['sessionId'], $session['status']);
            }
        }
    }

    /**
     * @param $listType string Can be one of 'webinar', 'meeting', 'training' or 'assist'
     * @return array
     */
    public static function getCitrixChoices($listType)
    {
        try {
            // Check if integration is enabled
            if (!self::isAuthorized(self::listToIntegration($listType))) {
                throw new \AuthenticationException('You are not authorized to view '.$listType);
            }

            if ('webinar' === $listType) {
                $results = self::getG2wApi()->request('upcomingWebinars');

                return iterator_to_array(self::getKeyPairs($results, 'webinarID', 'subject'));
            } else {
                if ('meeting' === $listType) {
                    $results = self::getG2mApi()->request('upcomingMeetings');

                    return iterator_to_array(self::getKeyPairs($results, 'meetingId', 'subject'));
                } else {
                    if ('training' === $listType) {
                        $results = self::getG2tApi()->request('trainings');

                        return iterator_to_array(self::getKeyPairs($results, 'trainingKey', 'name'));
                    } else {
                        if ('assist' === $listType) {
                            // show sessions in the last month
                            // times must be in ISO format: YYYY-MM-ddTHH:mm:ssZ
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
                                return iterator_to_array(self::getAssistPairs($results['sessions']));
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
    public static function isAuthorized($integration)
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
            // do nothing
        }

        return null;
    }

    /**
     * @param $listType
     * @return mixed
     */
    private static function listToIntegration($listType)
    {
        if (CitrixProducts::isValidValue($listType)) {
            return 'Goto'.$listType;
        }

        return '';
    }

    /**
     * @param string $str
     * @param int $limit
     * @return string
     */
    public static function getCleanString($str, $limit = 20)
    {
        $str = htmlentities(strtolower($str), ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        $availableChars = explode(' ', '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z');
        $safeStr = '';
        $safeChar = '';
        /** @var array $chars */
        $chars = str_split($str);
        foreach ($chars as $char) {
            if (!in_array($char, $availableChars, true)) {
                if ('-' !== $safeChar) {
                    $safeChar = '-';
                } else {
                    continue;
                }
            } else {
                $safeChar = $char;
            }
            $safeStr .= $safeChar;
        }

        return trim(substr($safeStr, 0, $limit), '-');
    }

    /**
     * @param $product
     * @param $productId
     * @param $email
     * @param $firstname
     * @param $lastname
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public static function registerToProduct($product, $productId, $email, $firstname, $lastname)
    {
        try {
            $response = [];
            if ($product === CitrixProducts::GOTOWEBINAR) {

                $params = [
                    'email' => $email,
                    'firstName' => $firstname,
                    'lastName' => $lastname,
                ];

                $response = self::getG2wApi()->request(
                    'webinars/'.$productId.'/registrants?resendConfirmation=true',
                    $params,
                    'POST'
                );
            } else {
                if ($product === CitrixProducts::GOTOTRAINING) {

                    $params = [
                        'email' => $email,
                        'givenName' => $firstname,
                        'surname' => $lastname,
                    ];

                    $response = self::getG2tApi()->request(
                        'trainings/'.$productId.'/registrants',
                        $params,
                        'POST'
                    );
                }
            }

            return (is_array($response) && array_key_exists('joinUrl', $response));
        } catch (\Exception $ex) {
            CitrixHelper::log('registerToProduct: '.$ex->getMessage());
            throw new BadRequestHttpException($ex->getMessage());
        }
    }

    /**
     * @param $product
     * @param $productId
     * @param $email
     * @param $firstname
     * @param $lastname
     * @return bool
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public static function startToProduct($product, $productId, $email, $firstname, $lastname)
    {
        try {
            if ($product === CitrixProducts::GOTOMEETING) {
                $response = self::getG2mApi()->request(
                    'meetings/'.$productId.'/start'
                );

                return (is_array($response) && array_key_exists('hostURL', $response)) ? $response['hostURL'] : '';
            } else {
                if ($product === CitrixProducts::GOTOTRAINING) {
                    $response = self::getG2tApi()->request(
                        'trainings/'.$productId.'/start'
                    );

                    return (is_array($response) && array_key_exists('hostURL', $response)) ? $response['hostURL'] : '';
                } else {
                    if ($product === CitrixProducts::GOTOASSIST) {
                        /** @var Router $router */
                        $router = self::getContainer()->get('router');
                        $params = [
                            'sessionStatusCallbackUrl' => $router
                                ->generate('mautic_citrix_sessionchanged', [],
                                    UrlGeneratorInterface::ABSOLUTE_URL),
                            'sessionType' => 'screen_sharing',
                            'partnerObject' => '',
                            'partnerObjectUrl' => '',
                            'customerName' => $firstname . ' ' . $lastname,
                            'customerEmail' => $email,
                            'machineUuid' => '',
                        ];

                        $response = self::getG2aApi()->request(
                            'sessions',
                            $params,
                            'POST'
                        );

                        return (is_array($response) &&
                            array_key_exists(
                                'startScreenSharing',
                                $response
                            ) && array_key_exists(
                                'launchUrl',
                                $response['startScreenSharing']
                            )) ? $response['startScreenSharing']['launchUrl'] : '';
                    }
                }
            }
        } catch (\Exception $ex) {
            CitrixHelper::log('startProduct: '.$ex->getMessage());
            throw new BadRequestHttpException($ex->getMessage());
        }

        return '';
    }

}
