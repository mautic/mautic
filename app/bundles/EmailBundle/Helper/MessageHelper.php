<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 *
 * BouncedEmail parsing modified from:
 * .---------------------------------------------------------------------------.
 * |  Software: PHPMailer-BMH (BouncedEmail Mail Handler)                            |
 * |   Version: 5.0.0rc1                                                       |
 * |   Contact: codeworxtech@users.sourceforge.net                             |
 * |      Info: http://phpmailer.codeworxtech.com                              |
 * | ------------------------------------------------------------------------- |
 * |    Author: Andy Prevost andy.prevost@worxteam.com (admin)                 |
 * | Copyright (c) 2002-2009, Andy Prevost. All Rights Reserved.               |
 * | ------------------------------------------------------------------------- |
 * |   License: Distributed under the General Public License (GPL)             |
 * |            (http://www.gnu.org/licenses/gpl.html)                         |
 * | This program is distributed in the hope that it will be useful - WITHOUT  |
 * | ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or     |
 * | FITNESS FOR A PARTICULAR PURPOSE.                                         |
 * .---------------------------------------------------------------------------
 */

namespace Mautic\EmailBundle\Helper;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Type;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedBackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;

/**
 * Class MessageHelper.
 *
 * @deprecated 2.11.0; to be removed in 3.0
 */
class MessageHelper
{
    /**
     * @var Bounce
     */
    protected $bouncer;

    /**
     * @var Unsubscribe
     */
    protected $unsubscriber;

    /**
     * @var FeedBackLoop
     */
    protected $looper;

    /**
     * MessageHelper constructor.
     *
     * @param Bounce       $bouncer
     * @param Unsubscribe  $unsubscriber
     * @param FeedBackLoop $looper
     */
    public function __construct(Bounce $bouncer, Unsubscribe $unsubscriber, FeedBackLoop $looper)
    {
        @trigger_error('MessageHelper is deprecated and to be removed in 3.0. Use the appropriate InterfaceProcessor instead.', E_USER_DEPRECATED);

        $this->bouncer      = $bouncer;
        $this->unsubscriber = $unsubscriber;
        $this->looper       = $looper;
    }

    /**
     * @deprecated 2.11.0 to be removed in 3.0; use the appropriate InterfaceProcessor instead
     *
     * @param Message    $message
     * @param bool|false $allowBounce
     * @param bool|false $allowUnsubscribe
     *
     * @return bool
     */
    public function analyzeMessage(Message $message, $allowBounce = false, $allowUnsubscribe = false)
    {
        $processed = false;
        if ($allowBounce) {
            if ($this->bouncer->setMessage($message)->process()) {
                $processed = true;
            }
        }

        if ($allowUnsubscribe) {
            if ($this->unsubscriber->setMessage($message)->process()) {
                $processed = true;
            } elseif ($this->looper->setMessage($message)->process()) {
                $processed = true;
            }
        }

        return $processed;
    }

    /**
     * @deprecated 2.11.0 to be removed in 3.0;
     *
     * next rule number (BODY): 0238 <br />
     * default category:        unrecognized: <br />
     * default rule no.:        0000 <br />
     */
    public static $rule_categories = [
        Category::ANTISPAM       => ['remove' => 0, 'bounce_type' => Type::BLOCKED],
        Category::AUTOREPLY      => ['remove' => 0, 'bounce_type' => Type::AUTOREPLY],
        Category::CONCURRENT     => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::CONTENT_REJECT => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::COMMAND_REJECT => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::INTERNAL_ERROR => ['remove' => 0, 'bounce_type' => Type::TEMPORARY],
        Category::DEFER          => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::DELAYED        => ['remove' => 0, 'bounce_type' => Type::TEMPORARY],
        Category::DNS_LOOP       => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::DNS_UNKNOWN    => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::FULL           => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::INACTIVE       => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::LATIN_ONLY     => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::OTHER          => ['remove' => 1, 'bounce_type' => Type::GENERIC],
        Category::OVERSIZE       => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::OUTOFOFFICE    => ['remove' => 0, 'bounce_type' => Type::SOFT],
        Category::UNKNOWN        => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::UNRECOGNIZED   => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::USER_REJECT    => ['remove' => 1, 'bounce_type' => Type::HARD],
        Category::WARNING        => ['remove' => 0, 'bounce_type' => Type::SOFT],
    ];

    /**
     * Defined bounce parsing rules for non-standard DSN.
     *
     * @deprecated 2.11.0 to be removed in 3.0
     *
     * @param string      $body       body of the email
     * @param string|null $knownEmail Bounced email if known through a x-failed-recipient header or the like and need to parse the body for a reason
     *
     * @return array $result an array include the following fields: 'email', 'bounce_type','remove','rule_no','rule_cat'
     *               if we could NOT detect the type of bounce, return rule_no = '0000'
     */
    public static function parseBody($body, $knownEmail = '')
    {
        $parser = new Bounce\BodyParser();

        return $parser->parse($body, $knownEmail);
    }

    /**
     * Defined bounce parsing rules for standard DSN (Delivery Status Notification).
     *
     * @deprecated 2.11.0 to be removed in 3.0
     *
     * @param string $dsn_msg    human-readable explanation
     * @param string $dsn_report delivery-status report
     * @param bool   $debug_mode show debug info. or not
     *
     * @return array $result an array include the following fields: 'email', 'bounce_type','remove','rule_no','rule_cat'
     *               if we could NOT detect the type of bounce, return rule_no = '0000'
     */
    public static function parseDsn($dsn_msg, $dsn_report)
    {
        $parser = new Bounce\DsnParser();

        return $parser->parse($dsn_msg, $dsn_report);
    }

    /**
     * @deprecated 2.11.0 to be removed in 3.0; use AddressList::parse instead
     *
     * @param $addresses
     *
     * @return array
     */
    public static function parseAddressList($addresses)
    {
        return Address::parseList($addresses);
    }
}
