<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;

use Mautic\EmailBundle\MonitoredEmail\Exception\BounceNotFound;
use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\MonitoredEmail\Processor\Address;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper;

/**
 * Class DsnParser.
 */
class DsnParser
{
    /**
     * @param Message $message
     *
     * @return BouncedEmail
     *
     * @throws BounceNotFound
     */
    public function getBounce(Message $message)
    {
        // Parse the bounce
        $dsnMessage = ($message->dsnMessage) ? $message->dsnMessage : $message->textPlain;
        $dsnReport  = $message->dsnReport;

        // Try parsing the report
        $report = $this->parse($dsnMessage, $dsnReport);

        if (!$report['email'] || Category::UNRECOGNIZED === $report['rule_cat']) {
            throw new BounceNotFound();
        }

        $bounce = new BouncedEmail();
        $bounce->setContactEmail($report['email'])
            ->setType($report['bounce_type'])
            ->setRuleCategory($report['rule_cat'])
            ->setRuleNumber($report['rule_no'])
            ->setIsFinal($report['remove']);

        return $bounce;
    }

    /**
     * @todo - refactor to get rid of the if/else statements
     *
     * @param $dsnMessage
     * @param $dsnReport
     *
     * @return array
     */
    public function parse($dsnMessage, $dsnReport)
    {
        // initialize the result array
        $result = [
            'email'       => '',
            'bounce_type' => false,
            'remove'      => 0,
            'rule_cat'    => Category::UNRECOGNIZED,
            'rule_no'     => '0000',
        ];
        $action        = false;
        $diagnosisCode = false;

        // ======= parse $dsnReport ======
        // get the recipient email
        if (
            preg_match('/Original-Recipient: rfc822;(.*)/i', $dsnReport, $match)
            ||
            preg_match('/Final-Recipient:\s?rfc822;(.*)/i', $dsnReport, $match)
        ) {
            if ($parsedAddressList = Address::parseList($match[1])) {
                $result['email'] = key($parsedAddressList);
            }
        }

        if (preg_match('/Action: (.+)/i', $dsnReport, $match)) {
            $action = strtolower(trim($match[1]));
        }

        // Could be multi-line , if the new line is beginning with SPACE or HTAB
        if (preg_match("/Diagnostic-Code:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/is", $dsnReport, $match)) {
            $diagnosisCode = $match[1];
        }
        // ======= rules ======
        if (empty($result['email'])) {
            /* email address is empty
             * rule: full
             * sample:   DSN Message only
             * User quota exceeded: SMTP <xxxxx@yourdomain.com>
             */
            if (preg_match("/quota exceed.*<(\S+@\S+\w)>/is", $dsnMessage, $match)) {
                $result['rule_cat'] = Category::FULL;
                $result['rule_no']  = '0161';
                $result['email']    = $match[1];
            }
        } else {
            /* action could be one of them as RFC:1894
             * "failed" / "delayed" / "delivered" / "relayed" / "expanded"
             */
            switch ($action) {
                case 'failed':
                    /* rule: full
                     * sample:
                     * Diagnostic-Code: X-Postfix; me.domain.com platform: said: 552 5.2.2 Over
                     *   quota (in reply to RCPT TO command)
                     */
                    if (preg_match('/over.*quota/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0105';
                    }

                    /* rule: full
                     * sample:
                     * Diagnostic-Code: SMTP; 552 Requested mailbox exceeds quota.
                     */
                    elseif (preg_match('/exceed.*quota/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0129';
                    }

                    /* rule: full
                     * sample 1:
                     * Diagnostic-Code: smtp;552 5.2.2 This message is larger than the current system limit or the recipient's mailbox is full. Create a shorter message body or remove attachments and try sending it again.
                     * sample 2:
                     * Diagnostic-Code: X-Postfix; host mta5.us4.domain.com.int[111.111.111.111] said:
                     *   552 recipient storage full, try again later (in reply to RCPT TO command)
                     * sample 3:
                     * Diagnostic-Code: X-HERMES; host 127.0.0.1[127.0.0.1] said: 551 bounce as<the
                     *   destination mailbox <xxxxx@yourdomain.com> is full> queue as
                     *   100.1.ZmxEL.720k.1140313037.xxxxx@yourdomain.com (in reply to end of
                     *   DATA command)
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*full/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0145';
                    }

                    /* rule: full
                     * sample:
                     * Diagnostic-Code: SMTP; 452 Insufficient system storage
                     */
                    elseif (preg_match('/Insufficient system storage/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0134';
                    }

                    /* rule: full
                     * sample 1:
                     * Diagnostic-Code: X-Postfix; cannot append message to destination file^M
                     *   /var/mail/dale.me89g: error writing message: File too large^M
                     * sample 2:
                     * Diagnostic-Code: X-Postfix; cannot access mailbox /var/spool/mail/b8843022 for^M
                     *   user xxxxx. error writing message: File too large
                     */
                    elseif (preg_match('/File too large/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0192';
                    }

                    /* rule: oversize
                     * sample:
                     * Diagnostic-Code: smtp;552 5.2.2 This message is larger than the current system limit or the recipient's mailbox is full. Create a shorter message body or remove attachments and try sending it again.
                     */
                    elseif (preg_match('/larger than.*limit/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::OVERSIZE;
                        $result['rule_no']  = '0146';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: X-Notes; User xxxxx (xxxxx@yourdomain.com) not listed in public Name & Address Book
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user)(.*)not(.*)list/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0103';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: smtp; 450 user path no exist
                     */
                    elseif (preg_match('/user path no exist/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0106';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 Relaying denied.
                     * sample 2:
                     * Diagnostic-Code: SMTP; 554 <xxxxx@yourdomain.com>: Relay access denied
                     * sample 3:
                     * Diagnostic-Code: SMTP; 550 relaying to <xxxxx@yourdomain.com> prohibited by administrator
                     */
                    elseif (preg_match('/Relay.*(?:denied|prohibited|disallowed)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0108';
                    }

                    /*rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 554 qq Sorry, no valid recipients (#5.1.3)
                     */
                    elseif (preg_match('/no.*valid.*(?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0185';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 «Dªk¦a§} - invalid address (#5.5.0)
                     * sample 2:
                     * Diagnostic-Code: SMTP; 550 Invalid recipient: <xxxxx@yourdomain.com>
                     * sample 3:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Invalid User
                     */
                    elseif (preg_match('/Invalid.*(?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0111';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 554 delivery error: dd Sorry your message to xxxxx@yourdomain.com cannot be delivered. This account has been disabled or discontinued [#102]. - mta173.mail.tpe.domain.com
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*(?:disabled|discontinued)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0114';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 554 delivery error: dd This user doesn't have a domain.com account (www.xxxxx@yourdomain.com) [0] - mta134.mail.tpe.domain.com
                     */
                    elseif (preg_match("/user doesn't have.*account/is", $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0127';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 5.1.1 unknown or illegal alias: xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/(?:unknown|illegal).*(?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0128';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 450 mailbox unavailable.
                     * sample 2:
                     * Diagnostic-Code: SMTP; 550 5.7.1 Requested action not taken: mailbox not available
                     */
                    elseif (preg_match("/(?:alias|account|recipient|address|email|mailbox|user).*(?:un|not\s+)available/is", $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0122';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 553 sorry, no mailbox here by that name (#5.7.1)
                     */
                    elseif (preg_match('/no (?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0123';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 User (xxxxx@yourdomain.com) unknown.
                     * sample 2:
                     * Diagnostic-Code: SMTP; 553 5.3.0 <xxxxx@yourdomain.com>... Addressee unknown, relay=[111.111.111.000]
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*unknown/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0125';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 user disabled
                     * sample 2:
                     * Diagnostic-Code: SMTP; 452 4.2.1 mailbox temporarily disabled: xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*disabled/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0133';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Recipient address rejected: No such user (xxxxx@yourdomain.com)
                     */
                    elseif (preg_match('/No such (?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0143';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 MAILBOX NOT FOUND
                     * sample 2:
                     * Diagnostic-Code: SMTP; 550 Mailbox ( xxxxx@yourdomain.com ) not found or inactivated
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*NOT FOUND/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0136';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: X-Postfix; host m2w-in1.domain.com[111.111.111.000] said: 551
                     * <xxxxx@yourdomain.com> is a deactivated mailbox (in reply to RCPT TO
                     * command)
                     */
                    elseif (preg_match('/deactivated (?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0138';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: X-Postfix; host m2w-in1.domain.com[111.111.111.000] said: 551 <example@example.com> is a
                     * deactivated mailbox
                     */
                    elseif (preg_match('/deactivated mailbox/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0138';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com> recipient rejected
                     * ...
                     * <<< 550 <xxxxx@yourdomain.com> recipient rejected
                     * 550 5.1.1 xxxxx@yourdomain.com... User unknown
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*reject/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0148';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: smtp; 5.x.0 - Message bounced by administrator  (delivery attempts: 0)
                     */
                    elseif (preg_match('/bounce.*administrator/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0151';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <maxqin> is now disabled with MTA service.
                     */
                    elseif (preg_match('/<.*>.*disabled/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0152';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 551 not our customer
                     */
                    elseif (preg_match('/not our customer/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0154';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: smtp; 5.1.0 - Unknown address error 540-'Error: Wrong recipients' (delivery attempts: 0)
                     */
                    elseif (preg_match('/Wrong (?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0159';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: smtp; 5.1.0 - Unknown address error 540-'Error: Wrong recipients' (delivery attempts: 0)
                     * sample 2:
                     * Diagnostic-Code: SMTP; 501 #5.1.1 bad address xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/(?:unknown|bad).*(?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0160';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Command RCPT User <xxxxx@yourdomain.com> not OK
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*not OK/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0186';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 5.7.1 Access-Denied-XM.SSR-001
                     */
                    elseif (preg_match('/Access.*Denied/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0189';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 5.1.1 <xxxxx@yourdomain.com>... email address lookup in domain map failed^M
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*lookup.*fail/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0195';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 User not a member of domain: <xxxxx@yourdomain.com>^M
                     */
                    elseif (preg_match('/(?:recipient|address|email|mailbox|user).*not.*member of domain/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0198';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550-"The recipient cannot be verified.  Please check all recipients of this^M
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*cannot be verified/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0202';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Unable to relay for xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/Unable to relay/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0203';
                    }

                    /* rule: unknown
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 xxxxx@yourdomain.com:user not exist
                     * sample 2:
                     * Diagnostic-Code: SMTP; 550 sorry, that recipient doesn't exist (#5.7.1)
                     */
                    elseif (preg_match("/(?:alias|account|recipient|address|email|mailbox|user).*(?:n't|not) exist/is", $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0205';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550-I'm sorry but xxxxx@yourdomain.com does not have an account here. I will not
                     */
                    elseif (preg_match('/not have an account/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0207';
                    }

                    /* rule: unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 This account is not allowed...xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*is not allowed/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0220';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: inactive user
                     */
                    elseif (preg_match('/inactive.*(?:alias|account|recipient|address|email|mailbox|user)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0135';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 550 xxxxx@yourdomain.com Account Inactive
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*Inactive/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0155';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Recipient address rejected: Account closed due to inactivity. No forwarding information is available.
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user) closed due to inactivity/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0170';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>... User account not activated
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user) not activated/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0177';
                    }

                    /* rule: inactive
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 User suspended
                     * sample 2:
                     * Diagnostic-Code: SMTP; 550 account expired
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*(?:suspend|expire)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0183';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 553 5.3.0 <xxxxx@yourdomain.com>... Recipient address no longer exists
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*no longer exist/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0184';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 553 VS10-RT Possible forgery or deactivated due to abuse (#5.1.1) 111.111.111.211^M
                     */
                    elseif (preg_match('/(?:forgery|abuse)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0196';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 553 mailbox xxxxx@yourdomain.com is restricted
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*restrict/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0209';
                    }

                    /* rule: inactive
                     * sample:
                     * Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: User status is locked.
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*locked/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INACTIVE;
                        $result['rule_no']  = '0228';
                    }

                    /* rule: user_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 553 User refused to receive this mail.
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user) refused/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::USER_REJECT;
                        $result['rule_no']  = '0156';
                    }

                    /* rule: user_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 501 xxxxx@yourdomain.com Sender email is not in my domain
                     */
                    elseif (preg_match('/sender.*not/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::USER_REJECT;
                        $result['rule_no']  = '0206';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 554 Message refused
                     */
                    elseif (preg_match('/Message refused/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0175';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 550 5.0.0 <xxxxx@yourdomain.com>... No permit
                     */
                    elseif (preg_match('/No permit/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0190';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 553 sorry, that domain isn't in my list of allowed rcpthosts (#5.5.3 - chkuser)
                     */
                    elseif (preg_match("/domain isn't in.*allowed rcpthost/is", $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0191';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 553 AUTH FAILED - xxxxx@yourdomain.com^M
                     */
                    elseif (preg_match('/AUTH FAILED/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0197';
                    }

                    /* rule: command_reject
                     * sample 1:
                     * Diagnostic-Code: SMTP; 550 relay not permitted^M
                     * sample 2:
                     * Diagnostic-Code: SMTP; 530 5.7.1 Relaying not allowed: xxxxx@yourdomain.com
                     */
                    elseif (preg_match('/relay.*not.*(?:permit|allow)/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0201';
                    }

                    /* rule: command_reject
                     * sample:
                     *
                     * Diagnostic-Code: SMTP; 550 not local host domain.com, not a gateway
                     */
                    elseif (preg_match('/not local host/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0204';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 500 Unauthorized relay msg rejected
                     */
                    elseif (preg_match('/Unauthorized relay/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0215';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 554 Transaction failed
                     */
                    elseif (preg_match('/Transaction.*fail/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0221';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: smtp;554 5.5.2 Invalid data in message
                     */
                    elseif (preg_match('/Invalid data/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0223';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Local user only or Authentication mechanism
                     */
                    elseif (preg_match('/Local user only/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0224';
                    }

                    /* rule: command_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 550-ds176.domain.com [111.111.111.211] is currently not permitted to
                     * relay through this server. Perhaps you have not logged into the pop/imap
                     * server in the last 30 minutes or do not have SMTP Authentication turned on
                     * in your email client.
                     */
                    elseif (preg_match('/not.*permit.*to/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0225';
                    }

                    /*
                     * rule: mailbox restricted;
                     * sample:
                     * The error that the other server returned was:
                     * Diagnostic-Code: SMTP; 550 5.7.1 RESOLVER.RST.NotAuthorized; not authorized
                     */
                    elseif (preg_match('/NotAuthorized/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::COMMAND_REJECT;
                        $result['rule_no']  = '0225';
                    }

                    /* rule: content_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Content reject. FAAAANsG60M9BmDT.1
                     */
                    elseif (preg_match('/Content reject/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::CONTENT_REJECT;
                        $result['rule_no']  = '0165';
                    }

                    /* rule: content_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 552 MessageWall: MIME/REJECT: Invalid structure
                     */
                    elseif (preg_match("/MIME\/REJECT/is", $diagnosisCode)) {
                        $result['rule_cat'] = Category::CONTENT_REJECT;
                        $result['rule_no']  = '0212';
                    }

                    /* rule: content_reject
                     * sample:
                     * Diagnostic-Code: smtp; 554 5.6.0 Message with invalid header rejected, id=13462-01 - MIME error: error: UnexpectedBound: part didn't end with expected boundary [in multipart message]; EOSToken: EOF; EOSType: EOF
                     */
                    elseif (preg_match('/MIME error/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::CONTENT_REJECT;
                        $result['rule_no']  = '0217';
                    }

                    /* rule: content_reject
                     * sample:
                     * Diagnostic-Code: SMTP; 553 Mail data refused by AISP, rule [169648].
                     */
                    elseif (preg_match('/Mail data refused.*AISP/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::CONTENT_REJECT;
                        $result['rule_no']  = '0218';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Host unknown
                     */
                    elseif (preg_match('/Host unknown/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0130';
                    } elseif (preg_match('/Host not found/i', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0130';
                    } elseif (preg_match('/Domain not found/i', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0130';
                    } elseif (preg_match('/Host or domain name not found/i', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0130';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 553 Specified domain is not allowed.
                     */
                    elseif (preg_match('/Specified domain.*not.*allow/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0180';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * Diagnostic-Code: X-Postfix; delivery temporarily suspended: connect to
                     * 111.111.11.112[111.111.11.112]: No route to host
                     */
                    elseif (preg_match('/No route to host/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0188';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * Diagnostic-Code: SMTP; 550 unrouteable address
                     */
                    elseif (preg_match('/unrouteable address/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0208';
                    }

                    /* rule: defer
                     * sample:
                     * Diagnostic-Code: SMTP; 451 System(u) busy, try again later.
                     */
                    elseif (preg_match('/System.*busy/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DEFER;
                        $result['rule_no']  = '0112';
                    }

                    /* rule: defer
                     * sample:
                     * Diagnostic-Code: SMTP; 451 mta172.mail.tpe.domain.com Resources temporarily unavailable. Please try again later.  [#4.16.4:70].
                     */
                    elseif (preg_match('/Resources temporarily unavailable/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DEFER;
                        $result['rule_no']  = '0116';
                    }

                    /* rule: antispam, deny ip
                     * sample:
                     * Diagnostic-Code: SMTP; 554 sender is rejected: 0,mx20,wKjR5bDrnoM2yNtEZVAkBg==.32467S2
                     */
                    elseif (preg_match('/sender is rejected/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0101';
                    }

                    /* rule: antispam, deny ip
                     * sample:
                     * Diagnostic-Code: SMTP; 554 <unknown[111.111.111.000]>: Client host rejected: Access denied
                     */
                    elseif (preg_match('/Client host rejected/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0102';
                    }

                    /* rule: antispam, mismatch ip
                     * sample:
                     * Diagnostic-Code: SMTP; 554 Connection refused(mx). MAIL FROM [xxxxx@yourdomain.com] mismatches client IP [111.111.111.000].
                     */
                    elseif (preg_match('/MAIL FROM(.*)mismatches client IP/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0104';
                    }

                    /* rule: antispam, deny ip
                     * sample:
                     * Diagnostic-Code: SMTP; 554 Please visit http:// antispam.domain.com/denyip.php?IP=111.111.111.000 (#5.7.1)
                     */
                    elseif (preg_match('/denyip/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0144';
                    }

                    /* rule: antispam, deny ip
                     * sample:
                     * Diagnostic-Code: SMTP; 554 Service unavailable; Client host [111.111.111.211] blocked using dynablock.domain.com; Your message could not be delivered due to complaints we received regarding the IP address you're using or your ISP. See http:// blackholes.domain.com/ Error: WS-02^M
                     */
                    elseif (preg_match('/client host.*blocked/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0201';
                    }

                    /* rule: antispam, reject
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Requested action not taken: mail IsCNAPF76kMDARUY.56621S2 is rejected,mx3,BM
                     */
                    elseif (preg_match('/mail.*reject/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0147';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 552 sorry, the spam message is detected (#5.6.0)
                     */
                    elseif (preg_match('/spam.*detect/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0162';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 554 5.7.1 Rejected as Spam see: http:// rejected.domain.com/help/spam/rejected.html
                     */
                    elseif (preg_match('/reject.*spam/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0216';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 553 5.7.1 <xxxxx@yourdomain.com>... SpamTrap=reject mode, dsn=5.7.1, Message blocked by BOX Solutions (www.domain.com) SpamTrap Technology, please contact the domain.com site manager for help: (ctlusr8012).^M
                     */
                    elseif (preg_match('/SpamTrap/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0200';
                    }

                    /* rule: antispam, mailfrom mismatch
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Verify mailfrom failed,blocked
                     */
                    elseif (preg_match('/Verify mailfrom failed/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0210';
                    }

                    /* rule: antispam, mailfrom mismatch
                     * sample:
                     * Diagnostic-Code: SMTP; 550 Error: MAIL FROM is mismatched with message header from address!
                     */
                    elseif (preg_match('/MAIL.*FROM.*mismatch/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0226';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 554 5.7.1 Message scored too high on spam scale.  For help, please quote incident ID 22492290.
                     */
                    elseif (preg_match('/spam scale/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0211';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 554 5.7.1 reject: Client host bypassing service provider's mail relay: ds176.domain.com
                     8?
                    elseif (preg_match ("/Client host bypass/is",$diagnosisCode)) {
                      $result['rule_cat']    = Category::ANTISPAM;
                      $result['rule_no']     = '0229';
                    }
                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 550 sorry, it seems as a junk mail
                     */
                    elseif (preg_match('/junk mail/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0230';
                    }

                    /* rule: antispam
                     * sample:
                     * Diagnostic-Code: SMTP; 553-Message filtered. Please see the FAQs section on spam
                     */
                    elseif (preg_match('/message filtered/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0227';
                    }

                    /* rule: antispam, subject filter
                     * sample:
                     * Diagnostic-Code: SMTP; 554 5.7.1 The message from (<xxxxx@yourdomain.com>) with the subject of ( *(ca2639) 7|-{%2E* : {2"(%EJ;y} (SBI$#$@<K*:7s1!=l~) matches a profile the Internet community may consider spam. Please revise your message before resending.
                     */
                    elseif (preg_match('/subject.*consider.*spam/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::ANTISPAM;
                        $result['rule_no']  = '0222';
                    }

                    /* rule: internal_error
                     * sample:
                     * Diagnostic-Code: SMTP; 451 Temporary local problem - please try later
                     */
                    elseif (preg_match('/Temporary local problem/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INTERNAL_ERROR;
                        $result['rule_no']  = '0142';
                    }

                    /* rule: internal_error
                     * sample:
                     * Diagnostic-Code: SMTP; 553 5.3.5 system config error
                     */
                    elseif (preg_match('/system config error/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::INTERNAL_ERROR;
                        $result['rule_no']  = '0153';
                    }

                    /* rule: delayed
                     * sample:
                     * Diagnostic-Code: X-Postfix; delivery temporarily suspended: conversation with^M
                     * 111.111.111.11[111.111.111.11] timed out while sending end of data -- message may be^M
                     * sent more than once
                     */
                    elseif (preg_match('/delivery.*suspend/is', $diagnosisCode)) {
                        $result['rule_cat'] = Category::DELAYED;
                        $result['rule_no']  = '0213';
                    }

                    // =========== rules based on the dsn_msg ===============
                    /* rule: unknown
                     * sample:
                     * ----- The following addresses had permanent fatal errors -----
                     * <xxxxx@yourdomain.com>
                     * ----- Transcript of session follows -----
                     * ... while talking to mta1.domain.com.:
                     * >>> DATA
                     * <<< 503 All recipients are invalid
                     * 554 5.0.0 Service unavailable
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user)(.*)invalid/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0107';
                    }

                    /* rule: unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * xxxxx@yourdomain.com... Deferred: No such file or directory
                     */
                    elseif (preg_match('/Deferred.*No such.*(?:file|directory)/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0141';
                    }

                    /* rule: unknown
                     * sample:
                     * Failed to deliver to '<xxxxx@yourdomain.com>'^M
                     * LOCAL module(account xxxx) reports:^M
                     * mail receiving disabled^M
                     */
                    elseif (preg_match('/mail receiving disabled/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0194';
                    }

                    /* rule: unknown
                     * sample:
                     * - These recipients of your message have been processed by the mail server:^M
                     * xxxxx@yourdomain.com; Failed; 5.1.1 (bad destination mailbox address)
                     */
                    elseif (preg_match('/bad.*(?:alias|account|recipient|address|email|mailbox|user)/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '227';
                    }

                    /* rule: full
                     * sample 1:
                     * This Message was undeliverable due to the following reason:
                     * The user(s) account is temporarily over quota.
                     * <xxxxx@yourdomain.com>
                     * sample 2:
                     *  Recipient address: xxxxx@yourdomain.com
                     *  Reason: Over quota
                     */
                    elseif (preg_match('/over.*quota/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0131';
                    }

                    /* rule: full
                     * sample:
                     * Sorry the recipient quota limit is exceeded.
                     * This message is returned as an error.
                     */
                    elseif (preg_match('/quota.*exceeded/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0150';
                    }

                    /* rule: full
                     * sample:
                     * The user to whom this message was addressed has exceeded the allowed mailbox
                     * quota. Please resend the message at a later time.
                     */
                    elseif (preg_match("/exceed.*\n?.*quota/i", $dsnMessage)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0187';
                    }

                    /* rule: full
                     * sample 1:
                     * Failed to deliver to '<xxxxx@yourdomain.com>'
                     * LOCAL module(account xxxxxx) reports:
                     * account is full (quota exceeded)
                     * sample 2:
                     * Error in fabiomod_sql_glob_init: no data source specified - database access disabled
                     * [Fri Feb 17 23:29:38 PST 2006] full error for caltsmy:
                     * that member's mailbox is full
                     * 550 5.0.0 <xxxxx@yourdomain.com>... Can't create output
                     */
                    elseif (preg_match('/(?:alias|account|recipient|address|email|mailbox|user).*full/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0132';
                    }

                    /* rule: full
                     * sample:
                     * gaosong "(0), ErrMsg=Mailbox space not enough (space limit is 10240KB)
                     */
                    elseif (preg_match('/space.*not.*enough/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::FULL;
                        $result['rule_no']  = '0219';
                    }

                    /* rule: defer
                     * sample 1:
                     * ----- Transcript of session follows -----
                     * xxxxx@yourdomain.com... Deferred: Connection refused by nomail.tpe.domain.com.
                     * Message could not be delivered for 5 days
                     * Message will be deleted from queue
                     * sample 2:
                     * 451 4.4.1 reply: read error from www.domain.com.
                     * xxxxx@yourdomain.com... Deferred: Connection reset by www.domain.com.
                     */
                    elseif (preg_match('/Deferred.*Connection (?:refused|reset)/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DEFER;
                        $result['rule_no']  = '0115';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- The following addresses had permanent fatal errors -----
                     * Tan XXXX SSSS <xxxxx@yourdomain..com>
                     * ----- Transcript of session follows -----
                     * 553 5.1.2 XXXX SSSS <xxxxx@yourdomain..com>... Invalid host name
                     */
                    elseif (preg_match('/Invalid host name/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0109';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * xxxxx@yourdomain.com... Deferred: mail.domain.com.: No route to host
                     */
                    elseif (preg_match('/Deferred.*No route to host/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0109';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * 550 5.1.2 xxxxx@yourdomain.com... Host unknown (Name server: .: no data known)
                     */
                    elseif (preg_match('/Host unknown/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0140';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * 451 HOTMAIL.com.tw: Name server timeout
                     * Message could not be delivered for 5 days
                     * Message will be deleted from queue
                     */
                    elseif (preg_match('/Name server timeout/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0118';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * xxxxx@yourdomain.com... Deferred: Connection timed out with hkfight.com.
                     * Message could not be delivered for 5 days
                     * Message will be deleted from queue
                     */
                    elseif (preg_match('/Deferred.*Connection.*tim(?:e|ed).*out/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0119';
                    }

                    /* rule: dns_unknown
                     * sample:
                     * ----- Transcript of session follows -----
                     * xxxxx@yourdomain.com... Deferred: Name server: domain.com.: host name lookup failure
                     */
                    elseif (preg_match('/Deferred.*host name lookup failure/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_UNKNOWN;
                        $result['rule_no']  = '0121';
                    }

                    /* rule: dns_loop
                     * sample:
                     * ----- Transcript of session follows -----^M
                     * 554 5.0.0 MX list for znet.ws. points back to mail01.domain.com^M
                     * 554 5.3.5 Local configuration error^M
                     */
                    elseif (preg_match('/MX list.*point.*back/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_LOOP;
                        $result['rule_no']  = '0199';
                    }

                    /* rule: dns_loop
                     * sample:
                     * ----- Transcript of session follows -----^M
                     * 554 5.4.6 Hop count exceeded - possible mail loop
                     */
                    elseif (preg_match('/Hop count exceeded/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::DNS_LOOP;
                        $result['rule_no']  = '0199';
                    }

                    /* rule: internal_error
                     * sample:
                     * ----- Transcript of session follows -----
                     * 451 4.0.0 I/O error
                     */
                    elseif (preg_match("/I\/O error/i", $dsnMessage)) {
                        $result['rule_cat'] = Category::INTERNAL_ERROR;
                        $result['rule_no']  = '0120';
                    }

                    /* rule: internal_error
                     * sample:
                     * Failed to deliver to 'xxxxx@yourdomain.com'^M
                     * SMTP module(domain domain.com) reports:^M
                     * connection with mx1.mail.domain.com is broken^M
                     */
                    elseif (preg_match('/connection.*broken/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::INTERNAL_ERROR;
                        $result['rule_no']  = '0231';
                    }

                    /* rule: other
                     * sample:
                     * Delivery to the following recipients failed.
                     * xxxxx@yourdomain.com
                     */
                    elseif (preg_match("/Delivery to the following recipients failed.*\n.*\n.*".$result['email'].'/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::OTHER;
                        $result['rule_no']  = '0176';
                    }

                    // Followings are wind-up rule: must be the last one
                    //   many other rules msg end up with "550 5.1.1 ... User unknown"
                    //   many other rules msg end up with "554 5.0.0 Service unavailable"

                    /* rule: unknown
                     * sample 1:
                     * ----- The following addresses had permanent fatal errors -----^M
                     * <xxxxx@yourdomain.com>^M
                     * (reason: User unknown)^M
                     * sample 2:
                     * 550 5.1.1 xxxxx@yourdomain.com... User unknown^M
                     */
                    elseif (preg_match('/User unknown/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0193';
                    }

                    /* rule: unknown
                     * sample:
                     * 554 5.0.0 Service unavailable
                     */
                    elseif (preg_match('/Service unavailable/i', $dsnMessage)) {
                        $result['rule_cat'] = Category::UNKNOWN;
                        $result['rule_no']  = '0214';
                    }
                    break;
                case Category::DELAYED:
                    $result['rule_cat'] = Category::DELAYED;
                    $result['rule_no']  = '0110';
                    break;
                case 'delivered':
                case 'relayed':
                case 'expanded': // unhandled cases
                    break;
                default:
                    break;
            }
        }

        if (false === $result['bounce_type']) {
            $categoryObject        = CategoryMapper::map($result['rule_cat']);
            $result['bounce_type'] = $categoryObject->getType();
            $result['remove']      = $categoryObject->isPermanent();
        }

        return $result;
    }
}
