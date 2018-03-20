<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @author      Jan Kozak <galvani78@gmail.com>
 */

namespace MauticPlugin\MauticMessageBirdBundle\Services;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Exception\BadConfigurationException;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\SmsBundle\Api\AbstractSmsApi;
use MauticPlugin\MauticMessageBirdBundle\Exception\RESTCallException;
use MessageBird\Client;
use MessageBird\Objects\Message;
use Monolog\Logger;

class MessageBirdApi extends AbstractSmsApi
{
    const MAX_SMS_SEND_AMOUNT = 50;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $originator;

    /**
     * MessageBirdApi constructor.
     *
     * @param TrackableModel    $pageTrackableModel
     * @param PhoneNumberHelper $phoneNumberHelper
     * @param IntegrationHelper $integrationHelper
     * @param Logger            $logger
     *
     * @throws BadConfigurationException
     */
    public function __construct(TrackableModel $pageTrackableModel, PhoneNumberHelper $phoneNumberHelper, IntegrationHelper $integrationHelper, Logger $logger)
    {
        $this->logger = $logger;

        $integration = $integrationHelper->getIntegrationObject('MessageBird');

        if ($integration && $integration->getIntegrationSettings()->getIsPublished()) {
            $this->originator = $integration->getIntegrationSettings()->getFeatureSettings()['sending_phone_number'];

            $keys = $integration->getDecryptedApiKeys();

            if (!isset($keys['apikey']) || strlen($keys['apikey']) != 25 || is_null($this->originator)) {
                throw new BadConfigurationException('MessageBird\'s configuration is invalid, check API access keys and sender.');
            }

            $this->client = new Client($keys['apikey']);
        }

        parent::__construct($pageTrackableModel);
    }

    /**
     * @param      $numberIn
     * @param bool $forceArrayReturn
     *
     * @todo check that the format returned is correct for MessageBird
     *
     * @return array|mixed
     *
     * @throws \libphonenumber\NumberParseException
     */
    private function sanitizeNumber($numberIn, $forceArrayReturn = false)
    {
        $numbers = is_array($numberIn) ? $numberIn : [$numberIn];
        $util    = PhoneNumberUtil::getInstance();

        foreach ($numbers as $index => $number) {
            $numbers[$index] = $util->format($util->parse($number, 'US'), PhoneNumberFormat::E164);
        }

        return is_array($numberIn) || $forceArrayReturn ? $numbers : array_shift($numbers);
    }

    /**
     * @param string $number
     * @param string $content
     *
     * @return bool|mixed|string
     *
     * @throws RESTCallException
     * @throws \Exception
     */
    public function sendSms($number, $content)
    {
        if (is_null($this->client)) {
            throw new \Exception('MessageBird API is not set up');
        }
        if ($number === null) {
            return false;
        }

        try {
            $recipients = $this->sanitizeNumber($number, true);
        } catch (NumberParseException $e) {
            return $e->getMessage();
        }

        // Just a minor check for batch processing
        if (count($recipients) > self::MAX_SMS_SEND_AMOUNT) {
            $this->logger->warn('You are attempting to send more SMS than MessageBird allows. Maximum is '.self::MAX_SMS_SEND_AMOUNT);
        }

        $message             = new Message();
        $message->originator = $this->originator;
        $message->recipients = $recipients;
        $message->body       = $content;

        //  Send the message
        try {
            $response = $this->client->messages->create($message);
            $this->logger->addDebug('MessageBird SMS sent successfully. MB response', (array) $response);

            if (is_array($response)) {
                $this->logger->addInfo('MessageBird SMS sent successfully '.count($response).'messages');

                return true;
            }

            $this->logger->addInfo('MessageBird SMS sent successfully. MB ID #'.$response->getId());

            return true;
        } catch (\MessageBird\Exceptions\AuthenticateException $e) {
            $this->logger->addError('MessageBird authentication failed. ', ['exception' => $e]);
        } catch (\MessageBird\Exceptions\BalanceException $e) {
            $this->logger->addError('MessageBird account has run out of credits. ', ['exception' => $e]);
        } catch (\Exception $e) {
            // Request failed. More information can be found in the body.
            $this->logger->addError('MessageBird request failed. ', ['exception' => $e]);
        }

        return $e->getMessage();

        //  Unfortunetly we return just a string and we are not allowed to catch exception for BC
        throw new RESTCallException('MessageBird API call failed.', ['exception' => $e]);
    }
}
