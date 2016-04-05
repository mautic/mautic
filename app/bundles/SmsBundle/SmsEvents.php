<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle;

/**
 * Class SmsEvents
 * Events available for SmsBundle
 *
 * @package Mautic\SmsBundle
 */
final class SmsEvents
{
    /**
     * The mautic.sms_on_send event is thrown when an email is sent
     *
     * The event listener receives a
     * Mautic\SmsBundle\Event\SmsSendEvent instance.
     *
     * @var string
     */
    const SMS_ON_SEND = 'mautic.sms_on_send';
}