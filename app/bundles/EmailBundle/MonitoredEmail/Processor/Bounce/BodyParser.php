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
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Definition\Category;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce\Mapper\CategoryMapper;

/**
 * Class BodyParser.
 */
class BodyParser
{
    /**
     * @param Message $message
     *
     * @return BouncedEmail
     *
     * @throws BounceNotFound
     */
    public function getBounce(Message $message, $contactEmail = null)
    {
        $report = $this->parse($message->textPlain, $contactEmail);

        if (!$report['email']) {
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
     * @param        $body
     * @param string $knownEmail
     *
     * @return array
     */
    public function parse($body, $knownEmail = '')
    {
        // initialize the result array
        $result = [
            'email'       => $knownEmail,
            'bounce_type' => false,
            'remove'      => 0,
            'rule_cat'    => Category::UNRECOGNIZED,
            'rule_no'     => '0000',
        ];

        // ======== rule =========

        /*
         * Email is already known likely for a x-failed-recipients header; most likely Gmail bounce
         */
        if ('' !== $knownEmail) {
            /*
             * rule: mailbox unknown;
             * sample:
             * The error that the other server returned was:
             * 550-5.1.1 The email account that you tried to reach does not exist.
             */
            if (preg_match('/email.*?does not exist/i', $body, $match)) {
                $result['rule_cat'] = Category::UNKNOWN;
                $result['rule_no']  = '0237';
            }

            /*
             * rule: mailbox unknown;
             * sample:
             * The error that the other server returned was:
             * 553-5.1.2 We weren't able to find the recipient domain.
             */
            elseif (preg_match('/find the recipient domain/i', $body, $match)) {
                $result['rule_cat'] = Category::UNKNOWN;
                $result['rule_no']  = '0237';
            }

            /*
             * rule: mailbox unknown;
             * sample:
             * The error that the other server returned was:
             * 550 5.1.1 RESOLVER.ADR.RecipNotFound; not found
             */
            elseif (preg_match('/RecipNotFound/i', $body, $match)) {
                $result['rule_cat'] = Category::UNKNOWN;
                $result['rule_no']  = '0237';
            }

            /*
             * rule: user reject;
             * sample:
             * The error that the other server returned was:
             * 554 5.7.1 Your mail could not be delivered because the recipient is only accepting mail from specific email addresses.
             */
            elseif (preg_match('/accepting mail from specific email addresses/i', $body, $match)) {
                $result['rule_cat'] = Category::USER_REJECT;
                $result['rule_no']  = '0156';
            }

            /*
             * rule: mailbox inactive;
             * sample:
             * The error that the other server returned was:
             * 550-5.2.1 The email account that you tried to reach is disabled.
             */
            elseif (preg_match('/email.*?disabled/i', $body, $match)) {
                $result['rule_cat'] = Category::INACTIVE;
                $result['rule_no']  = '0171';
            }

            /*
             * rule: mailbox warning;
             * sample:
             * The error that the other server returned was:
             * 550-5.2.1 The user you are trying to contact is receiving mail at a rate that prevents additional messages from being delivered.
             */
            elseif (preg_match('/user.*?rate that prevents/i', $body, $match)) {
                $result['rule_cat'] = Category::WARNING;
                $result['rule_no']  = '0000';
            }

            /*
            * rule: mailbox full;
            * sample:
            * The error that the other server returned was:
            * 550-5.7.1 Email quota exceeded.
            */
            elseif (preg_match('/email quota exceeded/i', $body, $match)) {
                $result['rule_cat'] = Category::FULL;
                $result['rule_no']  = '0219';
            }

            /*
            * rule: mailbox full;
            * sample:
            * The error that the other server returned was:
            * 552-5.2.2 The email account that you tried to reach is over quota.
            */
            if (preg_match('/email.*?over quota/i', $body, $match)) {
                $result['rule_cat'] = Category::FULL;
                $result['rule_no']  = '0219';
            }

            /*
            * rule: mailbox antispam;
            * sample:
            * The error that the other server returned was:
            * 550-5.7.1 Our system has detected an unusual rate of unsolicited mail originating from your IP address. To protect our users from spam,
            * mail sent from your IP address has been blocked.
            */
            elseif (preg_match('/unsolicited mail/i', $body, $match)) {
                $result['rule_cat'] = Category::ANTISPAM;
                $result['rule_no']  = '0230';
            }

            /*
            * rule: mailbox antispam;
            * sample:
            * The error that the other server returned was:
            * 550-5.7.1 The user or domain that you are sending to (or from) has a policy that prohibited the mail that you sent.
            */
            elseif (preg_match('/policy that prohibited/i', $body, $match)) {
                $result['rule_cat'] = Category::ANTISPAM;
                $result['rule_no']  = '0230';
            }

            /*
            * rule: mailbox oversize;
            * sample:
            * The error that the other server returned was:
            * 552-5.2.3 Your message exceeded Google's message size limits.
            */
            elseif (preg_match('/message size limits/i', $body, $match)) {
                $result['rule_cat'] = Category::OVERSIZE;
                $result['rule_no']  = '0146';
            }
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * xxxxx@yourdomain.com
        * no such address here
        */
        if (preg_match("/(\S+@\S+\w).*\n?.*no such address here/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0237';
            $result['email']    = $match[1];
        }

        /*
        * <xxxxx@yourdomain.com>:
        * 111.111.111.111 does not like recipient.
        * Remote host said: 550 User unknown
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*\n?.*user unknown/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0236';
            $result['email']    = $match[1];
        }

        /*
         * rule: mailbox unknown;
         * sample:
         * <xxxxx@yourdomain.com>:
         * Sorry, no mailbox here by that name. vpopmail (#5.1.1)
         */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*no mailbox/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0157';
            $result['email']    = $match[1];
        }

        /*
         * rule: mailbox unknown;
         * sample:
         * xxxxx@yourdomain.com<br>
         * local: Sorry, can't find user's mailbox. (#5.1.1)<br>
         */
        elseif (preg_match("/(\S+@\S+\w)<br>.*\n?.*\n?.*can't find.*mailbox/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0164';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        *     ##########################################################
        *     #  This is an automated response from a mail delivery    #
        *     #  program.  Your message could not be delivered to      #
        *     #  the following address:                                #
        *     #                                                        #
        *     #      "|/usr/local/bin/mailfilt -u #dkms"               #
        *     #        (reason: Can't create output)                   #
        *     #        (expanded from: <xxxxx@yourdomain.com>)         #
        *     #                                                        #
        */
        elseif (preg_match("/Can't create output.*\n?.*<(\S+@\S+\w)>/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0169';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * ????????????????:
        * xxxxx@yourdomain.com : ????, ?????.
        */
        elseif (preg_match("/(\S+@\S+\w).*=D5=CA=BA=C5=B2=BB=B4=E6=D4=DA/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0174';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * xxxxx@yourdomain.com
        * Unrouteable address
        */
        elseif (preg_match("/(\S+@\S+\w).*\n?.*Unrouteable address/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0179';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * Delivery to the following recipients failed.
        * xxxxx@yourdomain.com
        */
        elseif (preg_match("/delivery[^\n\r]+failed[ \S]*\s+(\S+@\S+\w)\s/is", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0013';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox error (Amazon SES);
        * sample:
        * An error occurred while trying to deliver the mail to the following recipients:
        * xxxxx@yourdomain.com
        */
        elseif (preg_match("/an\s+error\s+occurred\s+while\s+trying\s+to\s+deliver\s+the\s+mail\s+to\s+the\s+following\s+recipients:\r\n\s*(\S+@\S+\w)/is", $body, $match)) {
            $result['rule_cat']    = Category::UNKNOWN;
            $result['rule_no']     = '0013';
            $result['bounce_type'] = Type::HARD;
            $result['remove']      = 1;
            $result['email']       = $match[1];
            $result['email']       = preg_replace("/Reporting\-MTA/", '', $result['email']);
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * A message that you sent could not be delivered to one or more of its^M
        * recipients. This is a permanent error. The following address(es) failed:^M
        * ^M
        * xxxxx@yourdomain.com^M
        * unknown local-part "xxxxx" in domain "yourdomain.com"^M
        */
        elseif (preg_match("/(\S+@\S+\w).*\n?.*unknown local-part/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0232';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * <xxxxx@yourdomain.com>:^M
        * 111.111.111.11 does not like recipient.^M
        * Remote host said: 550 Invalid recipient: <xxxxx@yourdomain.com>^M
        */
        elseif (preg_match("/Invalid.*(?:alias|account|recipient|address|email|mailbox|user).*<(\S+@\S+\w)>/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0233';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * Sent >>> RCPT TO: <xxxxx@yourdomain.com>^M
        * Received <<< 550 xxxxx@yourdomain.com... No such user^M
        * ^M
        * Could not deliver mail to this user.^M
        * xxxxx@yourdomain.com^M
        * *****************     End of message     ***************^M
        */
        elseif (preg_match("/\s(\S+@\S+\w).*[\r\n]*.*No such.*(?:alias|account|recipient|address|email|mailbox|user)/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0234';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox unknown;
        * sample:
        * <xxxxx@yourdomain.com>:^M
        * This address no longer accepts mail.
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*(?:alias|account|recipient|address|email|mailbox|user).*no.*accept.*mail>/i", $body, $match)) {
            $result['rule_cat'] = Category::UNKNOWN;
            $result['rule_no']  = '0235';
            $result['email']    = $match[1];
        }

        /*
        * rule: full
        * sample 1:
        * <xxxxx@yourdomain.com>:
        * This account is over quota and unable to receive mail.
        * sample 2:
        * <xxxxx@yourdomain.com>:
        * Warning: undefined mail delivery mode: normal (ignored).
        * The users mailfolder is over the allowed quota (size). (#5.2.2)
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*\n?.*over.*quota/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0182';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox full;
        * sample:
        *   ----- Transcript of session follows -----
        * mail.local: /var/mail/2b/10/kellen.lee: Disc quota exceeded
        * 554 <xxxxx@yourdomain.com>... Service unavailable
        */
        elseif (preg_match("/quota exceeded.*\n?.*<(\S+@\S+\w)>/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0126';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox full;
        * sample:
        * Hi. This is the qmail-send program at 263.domain.com.
        * <xxxxx@yourdomain.com>:
        * - User disk quota exceeded. (#4.3.0)
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*quota exceeded/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0158';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox full;
        * sample:
        * xxxxx@yourdomain.com
        * mailbox is full (MTA-imposed quota exceeded while writing to file /mbx201/mbx011/A100/09/35/A1000935772/mail/.inbox):
        */
        elseif (preg_match("/\s(\S+@\S+\w)\s.*\n?.*mailbox.*full/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0166';
            $result['email']    = $match[1];

        /*
        * rule: mailbox full;
        * sample:
        * name@domain.com
        * Delay reason: LMTP error after end of data: 452 4.2.2 <name@domain.com> Mailbox is full / Blocks limit exceeded / Inode limit exceeded
        */
        } elseif (preg_match("/\s<(\S+@\S+\w)>\sMailbox.*full/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0166';
            $result['email']    = $match[1];
        }

        /*
        * rule: mailbox full;
        * sample:
        * The message to xxxxx@yourdomain.com is bounced because : Quota exceed the hard limit
        */
        elseif (preg_match("/The message to (\S+@\S+\w)\s.*bounce.*Quota exceed/i", $body, $match)) {
            $result['rule_cat'] = Category::FULL;
            $result['rule_no']  = '0168';
            $result['email']    = $match[1];
        }

        /*
        * rule: inactive
        * sample:
        * xxxxx@yourdomain.com<br>
        * 553 user is inactive (eyou mta)
        */
        elseif (preg_match("/(\S+@\S+\w)<br>.*\n?.*\n?.*user is inactive/i", $body, $match)) {
            $result['rule_cat'] = Category::INACTIVE;
            $result['rule_no']  = '0171';
            $result['email']    = $match[1];
        }

        /*
        * rule: inactive
        * sample:
        * xxxxx@yourdomain.com [Inactive account]
        */
        elseif (preg_match("/(\S+@\S+\w).*inactive account/i", $body, $match)) {
            $result['rule_cat'] = Category::INACTIVE;
            $result['rule_no']  = '0181';
            $result['email']    = $match[1];
        }

        /*
        * rule: internal_error
        * sample:
        * <xxxxx@yourdomain.com>:
        * Unable to switch to /var/vpopmail/domains/domain.com: input/output error. (#4.3.0)
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*input\/output error/i", $body, $match)) {
            $result['rule_cat']    = Category::INTERNAL_ERROR;
            $result['rule_no']     = '0172';
            $result['bounce_type'] = Type::HARD;
            $result['remove']      = 1;
            $result['email']       = $match[1];
        }

        /*
        * rule: internal_error
        * sample:
        * <xxxxx@yourdomain.com>:
        * can not open new email file errno=13 file=/home/vpopmail/domains/fromc.com/0/domain/Maildir/tmp/1155254417.28358.mx05,S=212350
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*can not open new email file/i", $body, $match)) {
            $result['rule_cat']    = Category::INTERNAL_ERROR;
            $result['rule_no']     = '0173';
            $result['bounce_type'] = Type::HARD;
            $result['remove']      = 1;
            $result['email']       = $match[1];
        }

        /*
        * rule: defer
        * sample:
        * <xxxxx@yourdomain.com>:
        * 111.111.111.111 failed after I sent the message.
        * Remote host said: 451 mta283.mail.scd.yahoo.com Resources temporarily unavailable. Please try again later [#4.16.5].
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*\n?.*Resources temporarily unavailable/i", $body, $match)) {
            $result['rule_cat'] = Category::DEFER;
            $result['rule_no']  = '0163';
            $result['email']    = $match[1];
        }

        /*
        * rule: autoreply
        * sample:
        * AutoReply message from xxxxx@yourdomain.com
        */
        elseif (preg_match("/^AutoReply message from (\S+@\S+\w)/i", $body, $match)) {
            $result['rule_cat'] = Category::AUTOREPLY;
            $result['rule_no']  = '0167';
            $result['email']    = $match[1];
        }

        /*
        * rule: western chars only
        * sample:
        * <xxxxx@yourdomain.com>:
        * The user does not accept email in non-Western (non-Latin) character sets.
        */
        elseif (preg_match("/<(\S+@\S+\w)>.*\n?.*does not accept[^\r\n]*non-Western/i", $body, $match)) {
            $result['rule_cat'] = Category::LATIN_ONLY;
            $result['rule_no']  = '0043';
            $result['email']    = $match[1];
        }

        if (false === $result['bounce_type']) {
            $categoryObject        = CategoryMapper::map($result['rule_cat']);
            $result['bounce_type'] = $categoryObject->getType();
            $result['remove']      = $categoryObject->isPermanent();
        }

        return $result;
    }
}
