<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Api;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Monolog\Logger;

class TwilioApi extends AbstractSmsApi
{
    /**
     * @var \Services_Twilio
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var string
     */
    protected $sendingPhoneNumber;

    /**
     * TwilioApi constructor.
     *
     * @param TrackableModel    $pageTrackableModel
     * @param \Services_Twilio  $client
     * @param PhoneNumberHelper $phoneNumberHelper
     * @param                   $sendingPhoneNumber
     * @param Logger            $logger
     */
    public function __construct(TrackableModel $pageTrackableModel, \Services_Twilio $client, PhoneNumberHelper $phoneNumberHelper, $sendingPhoneNumber, Logger $logger)
    {
        $this->client = $client;
        $this->logger = $logger;

        if ($sendingPhoneNumber) {
            $this->sendingPhoneNumber = $phoneNumberHelper->format($sendingPhoneNumber);
        }

        parent::__construct($pageTrackableModel);
    }

    /**
     * @param string $number
     *
     * @return string
     */
    protected function sanitizeNumber($number)
    {
        $util   = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @param string $number
     * @param string $content
     *
     * @return bool|string
     */
    public function sendSms($number, $content)
    {
        if ($number === null) {
            return false;
        }

        try {
            $this->client->account->messages->sendMessage(
                $this->sendingPhoneNumber,
                $this->sanitizeNumber($number),
                $content
            );

            return true;
        } catch (\Services_Twilio_RestException $e) {
            $this->logger->addWarning(
                $e->getMessage(),
                ['exception' => $e]
            );

            return $e->getMessage();
        } catch (NumberParseException $e) {
            $this->logger->addWarning(
                $e->getMessage(),
                ['exception' => $e]
            );

            return $e->getMessage();
        }
    }
}
