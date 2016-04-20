<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Api;

use Joomla\Http\Response;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Exception\MissingUsernameException;
use Mautic\SmsBundle\Exception\MissingPasswordException;

class TwilioApi extends AbstractSmsApi
{
    /**
     * @var \Services_Twilio
     */
    protected $client;

    /**
     * @var string
     */
    protected $sendingPhoneNumber;

    /**
     * @param \Services_Twilio $client
     * @param string $sendingPhoneNumber
     */
    public function __construct(\Services_Twilio $client, $sendingPhoneNumber)
    {
        $this->client = $client;
        $this->sendingPhoneNumber = $this->sanitizeNumber($sendingPhoneNumber);
    }

    /**
     * @param string $number
     *
     * @return string
     */
    protected function sanitizeNumber($number)
    {
        $util = PhoneNumberUtil::getInstance();
        $parsed = $util->parse($number, 'US');

        return $util->format($parsed, PhoneNumberFormat::E164);
    }

    /**
     * @param string $number
     * @param string $content
     *
     * @return \Services_Twilio_Rest_Message
     */
    public function sendSms($number, $content)
    {
        return $this->client->account->messages->sendMessage(
            $this->sendingPhoneNumber,
            $this->sanitizeNumber($number),
            $content
        );
    }
}