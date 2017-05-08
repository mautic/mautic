<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCitrixBundle\Helper;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticCitrixBundle\Api\GotoassistApi;
use MauticPlugin\MauticCitrixBundle\Api\GotomeetingApi;
use MauticPlugin\MauticCitrixBundle\Api\GototrainingApi;
use MauticPlugin\MauticCitrixBundle\Api\GotowebinarApi;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class CitrixHelper
{
    /**
     * @var LoggerInterface
     */
    private static $logger;

    /**
     * @var IntegrationHelper
     */
    private static $integratonHelper;

    /**
     * @param IntegrationHelper $helper
     * @param LoggerInterface   $logger
     */
    public static function init(IntegrationHelper $helper, LoggerInterface $logger)
    {
        self::$logger           = $logger;
        self::$integratonHelper = $helper;
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
            $class  = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotomeetingApi';
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
            $class  = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotowebinarApi';
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
            $class  = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GototrainingApi';
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
            $class  = '\\MauticPlugin\\MauticCitrixBundle\\Api\\GotoassistApi';
            $g2aapi = new $class(self::getIntegration('Gotoassist'));
        }

        return $g2aapi;
    }

    /**
     * @param        $msg
     * @param string $level
     */
    public static function log($msg, $level = 'error')
    {
        try {
            self::$logger->log($level, $msg);
        } catch (\Exception $ex) {
            // do nothing
        }
    }

    /**
     * @param array $results
     * @param       $key
     * @param       $value
     *
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
     * @param bool  $showAll  Wether or not to show only active sessions
     *
     * @return \Generator
     */
    public static function getAssistPairs($sessions, $showAll = false)
    {
        /** @var array $sessions */
        foreach ($sessions as $session) {
            if ($showAll || !in_array($session['status'], ['notStarted', 'abandoned'], true)) {
                yield $session['sessionId'] => sprintf('%s (%s)', $session['sessionId'], $session['status']);
            }
        }
    }

    /**
     * @param      $listType    string Can be one of 'webinar', 'meeting', 'training' or 'assist'
     * @param bool $onlyFutures
     *
     * @return array
     */
    public static function getCitrixChoices($listType, $onlyFutures = true)
    {
        try {
            // Check if integration is enabled
            if (!self::isAuthorized(self::listToIntegration($listType))) {
                throw new AuthenticationException('You are not authorized to view '.$listType);
            }
            $currentYear = date('Y');
            // TODO: the date range can be configured elsewhere
            $fromTime = ($currentYear - 10).'-01-01T00:00:00Z';
            $toTime   = ($currentYear + 10).'-01-01T00:00:00Z';
            if ('webinar' === $listType) {
                $url    = 'upcomingWebinars';
                $params = [];
                if (!$onlyFutures) {
                    $url                = 'historicalWebinars';
                    $params['fromTime'] = $fromTime;
                    $params['toTime']   = $toTime;
                }
                $results = self::getG2wApi()->request($url, $params);

                return iterator_to_array(self::getKeyPairs($results, 'webinarID', 'subject'));
            } else {
                if ('meeting' === $listType) {
                    $url    = 'upcomingMeetings';
                    $params = [];
                    if (!$onlyFutures) {
                        $url                 = 'historicalMeetings';
                        $params['startDate'] = $fromTime;
                        $params['endDate']   = $toTime;
                    }
                    $results = self::getG2mApi()->request($url, $params);

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
                                'toTime'      => preg_filter('/^(.+)[\+\-].+$/', '$1Z', date('c')),
                                'sessionType' => 'screen_sharing',
                            ];
                            $results = self::getG2aApi()->request('sessions', $params);
                            if ((array) $results && array_key_exists('sessions', $results)) {
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
     *
     * @return bool
     */
    public static function isAuthorized($integration)
    {
        $myIntegration = self::getIntegration($integration);

        return $myIntegration && $myIntegration->getIntegrationSettings() && $myIntegration->getIntegrationSettings()->getIsPublished();
    }

    /**
     * @param $integration
     *
     * @return AbstractIntegration
     */
    private static function getIntegration($integration)
    {
        try {
            return self::$integratonHelper->getIntegrationObject($integration);
        } catch (\Exception $e) {
            // do nothing
        }

        return null;
    }

    /**
     * @param $listType
     *
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
     * @param int    $limit
     *
     * @return string
     */
    public static function getCleanString($str, $limit = 20)
    {
        $str = htmlentities(strtolower($str), ENT_NOQUOTES, 'utf-8');
        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);

        $availableChars = explode(' ', '0 1 2 3 4 5 6 7 8 9 a b c d e f g h i j k l m n o p q r s t u v w x y z');
        $safeStr        = '';
        $safeChar       = '';
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
     *
     * @return bool
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public static function registerToProduct($product, $productId, $email, $firstname, $lastname)
    {
        try {
            $response = [];
            if ($product === CitrixProducts::GOTOWEBINAR) {
                $params = [
                    'email'     => $email,
                    'firstName' => $firstname,
                    'lastName'  => $lastname,
                ];

                $response = self::getG2wApi()->request(
                    'webinars/'.$productId.'/registrants?resendConfirmation=true',
                    $params,
                    'POST'
                );
            } else {
                if ($product === CitrixProducts::GOTOTRAINING) {
                    $params = [
                        'email'     => $email,
                        'givenName' => $firstname,
                        'surname'   => $lastname,
                    ];

                    $response = self::getG2tApi()->request(
                        'trainings/'.$productId.'/registrants',
                        $params,
                        'POST'
                    );
                }
            }

            return is_array($response) && array_key_exists('joinUrl', $response);
        } catch (\Exception $ex) {
            self::log('registerToProduct: '.$ex->getMessage());
            throw new BadRequestHttpException($ex->getMessage());
        }
    }

    /**
     * @param $product
     * @param $productId
     * @param $email
     * @param $firstname
     * @param $lastname
     *
     * @return bool
     *
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
                        // TODO: use the sessioncallback to update attendance status
                        /** @var Router $router */
                        $router = self::getContainer()->get('router');
                        $params = [
                            'sessionStatusCallbackUrl' => $router
                                ->generate(
                                    'mautic_citrix_sessionchanged',
                                    [],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                ),
                            'sessionType'      => 'screen_sharing',
                            'partnerObject'    => '',
                            'partnerObjectUrl' => '',
                            'customerName'     => $firstname.' '.$lastname,
                            'customerEmail'    => $email,
                            'machineUuid'      => '',
                        ];

                        $response = self::getG2aApi()->request(
                            'sessions',
                            $params,
                            'POST'
                        );

                        return (is_array($response)
                            && array_key_exists(
                                'startScreenSharing',
                                $response
                            )
                            && array_key_exists(
                                'launchUrl',
                                $response['startScreenSharing']
                            )) ? $response['startScreenSharing']['launchUrl'] : '';
                    }
                }
            }
        } catch (\Exception $ex) {
            self::log('startProduct: '.$ex->getMessage());
            throw new BadRequestHttpException($ex->getMessage());
        }

        return '';
    }

    /**
     * @param string $product
     * @param string $productId
     *
     * @return string
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public static function getEventName($product, $productId)
    {
        if (CitrixProducts::GOTOWEBINAR === $product) {
            $result = self::getG2wApi()->request($product.'s/'.$productId);

            return $result['subject'];
        } else {
            if (CitrixProducts::GOTOMEETING === $product) {
                $result = self::getG2mApi()->request($product.'s/'.$productId);

                return $result[0]['subject'];
            } else {
                if (CitrixProducts::GOTOTRAINING === $product) {
                    $result = self::getG2tApi()->request($product.'s/'.$productId);

                    return $result['name'];
                }
            }
        }

        return $productId;
    }

    /**
     * @param string $product
     * @param string $productId
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public static function getRegistrants($product, $productId)
    {
        $result = [];
        switch ($product) {
            case CitrixProducts::GOTOWEBINAR:
                $result = self::getG2wApi()->request($product.'s/'.$productId.'/registrants');
                break;

            case CitrixProducts::GOTOTRAINING:
                $result = self::getG2tApi()->request($product.'s/'.$productId.'/registrants');
                break;
        }

        return self::extractContacts($result);
    }

    /**
     * @param string $product
     * @param string $productId
     *
     * @return array
     *
     * @throws \Mautic\PluginBundle\Exception\ApiErrorException
     */
    public static function getAttendees($product, $productId)
    {
        $result = [];
        switch ($product) {
            case CitrixProducts::GOTOWEBINAR:
                $result = self::getG2wApi()->request($product.'s/'.$productId.'/attendees');
                break;

            case CitrixProducts::GOTOMEETING:
                $result = self::getG2mApi()->request($product.'s/'.$productId.'/attendees');
                break;

            case CitrixProducts::GOTOTRAINING:
                $reports  = self::getG2tApi()->request($product.'s/'.$productId, [], 'GET', 'rest/reports');
                $sessions = array_map(create_function('$o', 'return $o["sessionKey"];'), $reports);
                foreach ($sessions as $session) {
                    $result = self::getG2tApi()->request(
                        'sessions/'.$session.'/attendees',
                        [],
                        'GET',
                        'rest/reports'
                    );
                    $arr    = array_map(create_function('$o', 'return $o["email"];'), $result);
                    $result = array_merge($result, $arr);
                }

                break;
        }

        return self::extractContacts($result);
    }

    /**
     * @param $results
     *
     * @return array
     */
    protected static function extractContacts($results)
    {
        $contacts = [];

        foreach ($results as $result) {
            $emailKey = false;
            if (isset($result['attendeeEmail'])) {
                if (empty($result['attendeeEmail'])) {
                    // ignore
                    continue;
                }
                $emailKey = strtolower($result['attendeeEmail']);
                $names    = explode(' ', $result['attendeeName']);
                switch (count($names)) {
                    case 1:
                        $firstname = $names[0];
                        $lastname  = '';
                        break;
                    case 2:
                        list($firstname, $lastname) = $names;
                        break;
                    default:
                        $firstname = $names[0];
                        unset($names[0]);
                        $lastname = implode(' ', $names);
                }

                $contacts[$emailKey] = [
                    'firstname' => $firstname,
                    'lastname'  => $lastname,
                    'email'     => $result['attendeeEmail'],
                ];
            } elseif (!empty($result['email'])) {
                $emailKey            = strtolower($result['email']);
                $contacts[$emailKey] = [
                    'firstname' => (isset($result['firstName'])) ? $result['firstName'] : '',
                    'lastname'  => (isset($result['lastName'])) ? $result['lastName'] : '',
                    'email'     => $result['email'],
                    'joinUrl'   => (isset($result['joinUrl'])) ? $result['joinUrl'] : '',
                ];
            }

            if ($emailKey) {
                $eventDate = null;
                // Extract join/register time
                if (!empty($result['attendance']['joinTime'])) {
                    $eventDate = $result['attendance']['joinTime'];
                } elseif (!empty($result['joinTime'])) {
                    $eventDate = $result['joinTime'];
                } elseif (!empty($result['inSessionTimes']['joinTime'])) {
                    $eventDate = $result['inSessionTimes']['joinTime'];
                } elseif (!empty($result['registrationDate'])) {
                    $eventDate = $result['registrationDate'];
                }

                if ($eventDate) {
                    $contacts[$emailKey]['event_date'] = new \DateTime($eventDate);
                }
            }
        }

        return $contacts;
    }
}
