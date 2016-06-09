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
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\PhoneNumberHelper;
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
     * @param MauticFactory $factory
     * @param \Services_Twilio $client
     * @param string $sendingPhoneNumber
     */
    public function __construct(MauticFactory $factory, \Services_Twilio $client, PhoneNumberHelper $phoneNumberHelper, $sendingPhoneNumber)
    {
        $this->client = $client;
        
        if ($sendingPhoneNumber) {
            $this->sendingPhoneNumber = $phoneNumberHelper->format($sendingPhoneNumber);
        }

        parent::__construct($factory);
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
     * @return array
     */
    public function sendSms($number, $content)
    {
        if ($number === null) {
            return false;
        }

        try
        {
            $this->client->account->messages->sendMessage(
                $this->sendingPhoneNumber,
                $this->sanitizeNumber($number),
                $content
            );

            return true;
        } catch (\Services_Twilio_RestException $e) {
            $this->factory->getLogger()->addError(
                $e->getMessage(),
                array('exception' => $e)
            );

            return false;
        }
    }
}