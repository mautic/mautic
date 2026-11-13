<?php

namespace Mautic\EmailBundle\MonitoredEmail;

use Horde\Imap\Client\ConnectionConfig;
use Horde\Imap\Client\ImapClient;
use Horde\Imap\Client\ImapFetchQuery;
use Horde\Imap\Client\ImapIdSet;
use Horde\Imap\Client\ImapSearchQuery;
use Horde\Imap\Client\MailboxListMode;
use Horde\Imap\Client\OpenMode;
use Horde\Imap\Client\SecureMode;
use Horde\Mail\Rfc2047;
use Horde\Mail\Rfc822\Rfc822Parser;
use Horde\Mime\Headers\HeaderCollection;
use Horde\Mime\MimeParser;
use Horde\Mime\Part;
use Horde\Sasl\Credentials\PasswordCredentials;
use Horde\Sasl\Credentials\PlainSecret;
use IMAP\Connection;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Exception\MailboxException;
use Mautic\EmailBundle\MonitoredEmail\Exception\NotConfiguredException;

class Mailbox
{
    /**
     * Return all mails matching the rest of the criteria.
     */
    public const CRITERIA_ALL = 'ALL';

    /**
     * Match mails with the \\ANSWERED flag set.
     */
    public const CRITERIA_ANSWERED = 'ANSWERED';

    /**
     * CRITERIA_BCC "string" - match mails with "string" in the Bcc: field.
     */
    public const CRITERIA_BCC = 'BCC';

    /**
     * CRITERIA_BEFORE "date" - match mails with Date: before "date".
     */
    public const CRITERIA_BEFORE = 'BEFORE';

    /**
     * CRITERIA_BODY "string" - match mails with "string" in the body of the mail.
     */
    public const CRITERIA_BODY = 'BODY';

    /**
     * CRITERIA_CC "string" - match mails with "string" in the Cc: field.
     */
    public const CRITERIA_CC = 'CC';

    /**
     * Match deleted mails.
     */
    public const CRITERIA_DELETED = 'DELETED';

    /**
     * Match mails with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set.
     */
    public const CRITERIA_FLAGGED = 'FLAGGED';

    /**
     * CRITERIA_FROM "string" - match mails with "string" in the From: field.
     */
    public const CRITERIA_FROM = 'FROM';

    /**
     *  CRITERIA_KEYWORD "string" - match mails with "string" as a keyword.
     */
    public const CRITERIA_KEYWORD = 'KEYWORD';

    /**
     * Match new mails.
     */
    public const CRITERIA_NEW = 'NEW';

    /**
     * Match old mails.
     */
    public const CRITERIA_OLD = 'OLD';

    /**
     * CRITERIA_ON "date" - match mails with Date: matching "date".
     */
    public const CRITERIA_ON = 'ON';

    /**
     * Match mails with the \\RECENT flag set.
     */
    public const CRITERIA_RECENT = 'RECENT';

    /**
     * Match mails that have been read (the \\SEEN flag is set).
     */
    public const CRITERIA_SEEN = 'SEEN';

    /**
     * CRITERIA_SINCE "date" - match mails with Date: after "date".
     */
    public const CRITERIA_SINCE = 'SINCE';

    /**
     *  CRITERIA_SUBJECT "string" - match mails with "string" in the Subject:.
     */
    public const CRITERIA_SUBJECT = 'SUBJECT';

    /**
     * CRITERIA_TEXT "string" - match mails with text "string".
     */
    public const CRITERIA_TEXT = 'TEXT';

    /**
     * CRITERIA_TO "string" - match mails with "string" in the To:.
     */
    public const CRITERIA_TO = 'TO';

    /**
     *  Get messages since a specific UID. Eg. UID 2:* will return all messages with UID 2 and above (IMAP includes the given UID).
     */
    public const CRITERIA_UID = 'UID';

    /**
     *  Match mails that have not been answered.
     */
    public const CRITERIA_UNANSWERED = 'UNANSWERED';

    /**
     * Match mails that are not deleted.
     */
    public const CRITERIA_UNDELETED = 'UNDELETED';

    /**
     * Match mails that are not flagged.
     */
    public const CRITERIA_UNFLAGGED = 'UNFLAGGED';

    /**
     * CRITERIA_UNKEYWORD "string" - match mails that do not have the keyword "string".
     */
    public const CRITERIA_UNKEYWORD = 'UNKEYWORD';

    /**
     * Match mails which have not been read yet.
     */
    public const CRITERIA_UNSEEN = 'UNSEEN';

    /**
     * Match mails which have not been read yet - alias of CRITERIA_UNSEEN.
     */
    public const CRITERIA_UNREAD = 'UNSEEN';

    protected $imapPath;

    protected $imapFullPath;

    protected $imapStream;

    protected $imapFolder     = 'INBOX';

    protected $imapOptions    = 0;

    protected $imapRetriesNum = 0;

    protected $imapParams     = [];

    protected $serverEncoding = 'UTF-8';

    protected $attachmentsDir;

    protected $settings;

    protected $isGmail = false;

    protected $mailboxes;

    private ?ImapClient $client = null;

    /**
     * @var mixed[]
     */
    private array $folders = [];

    public function __construct(CoreParametersHelper $parametersHelper, PathsHelper $pathsHelper)
    {
        $this->mailboxes = $parametersHelper->get('monitored_email', []);

        if (isset($this->mailboxes['general'])) {
            $this->settings = $this->mailboxes['general'];
        } else {
            $this->settings = [
                'host'            => '',
                'port'            => '',
                'password'        => '',
                'user'            => '',
                'encryption'      => '',
                'use_attachments' => false,
            ];
        }

        $this->createAttachmentsDir($pathsHelper);

        if ('imap.gmail.com' == $this->settings['host']) {
            $this->isGmail = true;
        }
    }

    /**
     * Returns if a mailbox is configured.
     */
    public function isConfigured($bundleKey = null, $folderKey = null): bool
    {
        if (null !== $bundleKey) {
            try {
                $this->switchMailbox($bundleKey, $folderKey);
            } catch (MailboxException) {
                return false;
            }
        }

        return
            !empty($this->settings['host']) && !empty($this->settings['port']) && !empty($this->settings['user'])
            && !empty($this->settings['password']);
    }

    /**
     * Switch to another configured monitored mailbox.
     *
     * @param string $mailbox
     *
     * @throws MailboxException
     */
    public function switchMailbox($bundle, $mailbox = ''): void
    {
        $key = $bundle.(!empty($mailbox) ? '_'.$mailbox : '');

        if (isset($this->mailboxes[$key])) {
            $this->settings           = (!empty($this->mailboxes[$key]['override_settings'])) ? $this->mailboxes[$key] : $this->mailboxes['general'];
            $this->imapFolder         = $this->mailboxes[$key]['folder'];
            $this->settings['folder'] = $this->mailboxes[$key]['folder'];
            // Disconnect so that new mailbox settings are used
            $this->disconnect();
            // Setup new connection
            $this->setImapPath();
        } else {
            throw new MailboxException($key.' not found');
        }
    }

    /**
     * Returns if this is a Gmail connection.
     *
     * @return mixed
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function isGmail()
    {
        return $this->isGmail;
    }

    /**
     * Set imap path based on mailbox settings.
     */
    public function setImapPath($settings = null): void
    {
        if (null == $settings) {
            $settings = $this->settings;
        }
        $paths              = $this->getImapPath($settings);
        $this->imapPath     = $paths['path'];
        $this->imapFullPath = $paths['full'];
    }

    public function getImapPath(array $settings): array
    {
        if (!isset($settings['encryption'])) {
            $settings['encryption'] = (!empty($settings['ssl'])) ? '/ssl' : '';
        }
        $path     = "{{$settings['host']}:{$settings['port']}/imap{$settings['encryption']}}";
        $fullPath = $path;

        if (isset($settings['folder'])) {
            $fullPath .= $settings['folder'];
        }

        return ['path' => $path, 'full' => $fullPath];
    }

    /**
     * Override mailbox settings.
     */
    public function setMailboxSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);

        $this->isGmail = ('imap.gmail.com' == $this->settings['host']);

        $this->setImapPath();
        $this->disconnect();
    }

    /**
     * Get settings.
     *
     * @param string $mailbox
     *
     * @return mixed
     *
     * @throws MailboxException
     */
    public function getMailboxSettings($bundle = null, $mailbox = '')
    {
        if (null == $bundle) {
            return $this->settings;
        }

        $key = $bundle.(!empty($mailbox) ? '_'.$mailbox : '');

        if (isset($this->mailboxes[$key])) {
            $settings = (!empty($this->mailboxes[$key]['override_settings'])) ? $this->mailboxes[$key] : $this->mailboxes['general'];

            $settings['folder'] = $this->mailboxes[$key]['folder'];
            $this->setImapPath($settings);

            $imapPath              = $this->getImapPath($settings);
            $settings['imap_path'] = $imapPath['full'];
        } else {
            throw new MailboxException($key.' not found');
        }

        return $settings;
    }

    /**
     * Set custom connection arguments of imap_open method. See http://php.net/imap_open.
     *
     * @param int $options
     * @param int $retriesNum
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function setConnectionArgs($options = 0, $retriesNum = 0, ?array $params = null): void
    {
        $this->imapOptions    = $options;
        $this->imapRetriesNum = $retriesNum;
        $this->imapParams     = $params;
    }

    /**
     * Switch to another box.
     */
    public function switchFolder($folder): void
    {
        if ($folder != $this->imapFolder) {
            $this->imapFullPath = $this->imapPath.$folder;
            $this->imapFolder   = $folder;
        }

        $this->getImapStream();
    }

    /**
     * Get IMAP mailbox connection stream.
     *
     * @return Connection
     */
    public function getImapStream()
    {
        if (!$this->isConnected()) {
            $this->imapStream = $this->initImapStream();
        } else {
            @imap_reopen($this->imapStream, $this->imapFullPath);
        }

        return $this->imapStream;
    }

    /**
     * @return Connection
     *
     * @throws MailboxException
     */
    protected function initImapStream()
    {
        imap_timeout(IMAP_OPENTIMEOUT, 15);
        imap_timeout(IMAP_CLOSETIMEOUT, 15);
        imap_timeout(IMAP_READTIMEOUT, 15);
        imap_timeout(IMAP_WRITETIMEOUT, 15);

        $imapStream = @imap_open(
            $this->imapFullPath,
            $this->settings['user'],
            $this->settings['password'],
            $this->imapOptions,
            $this->imapRetriesNum,
            $this->imapParams
        );
        if (!$imapStream) {
            throw new MailboxException();
        }

        return $imapStream;
    }

    /**
     * Check if the stream is connected.
     */
    protected function isConnected(): bool
    {
        if (!$this->isConfigured() || !$this->imapStream) {
            return false;
        }

        try {
            return @imap_ping($this->imapStream);
        } catch (\ValueError|\TypeError) {
            return false;
        }
    }

    /**
     * Get information about the current mailbox.
     *
     * Returns the information in an object with following properties:
     *  Date - current system time formatted according to RFC2822
     *  Driver - protocol used to access this mailbox: POP3, IMAP, NNTP
     *  Mailbox - the mailbox name
     *  Nmsgs - number of mails in the mailbox
     *  Recent - number of recent mails in the mailbox
     *
     * @return \stdClass
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function checkMailbox(): \stdClass|bool
    {
        return imap_check($this->getImapStream());
    }

    /**
     * Creates a new mailbox specified by mailbox.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function createMailbox(): bool
    {
        return imap_createmailbox($this->getImapStream(), imap_utf7_encode($this->imapFullPath));
    }

    /**
     * Gets status information about the given mailbox.
     *
     * This function returns an object containing status information.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @return \stdClass if the box doesn't exist
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function statusMailbox(): \stdClass|bool
    {
        return imap_status($this->getImapStream(), $this->imapFullPath, SA_ALL);
    }

    /**
     * This function returns an object containing listing the folders.
     * The object has the following properties: messages, recent, unseen, uidnext, and uidvalidity.
     *
     * @return array listing the folders
     */
    public function getListingFolders()
    {
        if (!$this->isConfigured()) {
            throw new NotConfiguredException('mautic.email.config.monitored_email.not_configured');
        }

        $cacheKey = $this->settings['host'].':'.$this->settings['port'].'/'.$this->settings['user'];

        if (!isset($this->folders[$cacheKey])) {
            $this->folders[$cacheKey] = $this->getClient()->listMailboxes('*', MailboxListMode::All, ['flat' => true]);
        }

        return $this->folders[$cacheKey];
    }

    /**
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function fetchUnread($folder = null): array
    {
        if (null !== $folder) {
            $this->switchFolder($folder);
        }

        return $this->searchMailBox(self::CRITERIA_UNSEEN);
    }

    /**
     * This function performs a search on the mailbox currently opened in the given IMAP stream.
     * For example, to match all unanswered mails sent by Mom, you'd use: "UNANSWERED FROM mom".
     * Searches appear to be case insensitive. This list of criteria is from a reading of the UW
     * c-client source code and may be incomplete or inaccurate (see also RFC2060, section 6.4.4).
     *
     * @param string $criteria String, delimited by spaces, in which the following keywords are allowed. Any multi-word arguments (e.g. FROM "joey
     *                         smith") must be quoted. Results will match all criteria entries.
     *                         ALL - return all mails matching the rest of the criteria
     *                         ANSWERED - match mails with the \\ANSWERED flag set
     *                         BCC "string" - match mails with "string" in the Bcc: field
     *                         BEFORE "date" - match mails with Date: before "date"
     *                         BODY "string" - match mails with "string" in the body of the mail
     *                         CC "string" - match mails with "string" in the Cc: field
     *                         DELETED - match deleted mails
     *                         FLAGGED - match mails with the \\FLAGGED (sometimes referred to as Important or Urgent) flag set
     *                         FROM "string" - match mails with "string" in the From: field
     *                         KEYWORD "string" - match mails with "string" as a keyword
     *                         NEW - match new mails
     *                         OLD - match old mails
     *                         ON "date" - match mails with Date: matching "date"
     *                         RECENT - match mails with the \\RECENT flag set
     *                         SEEN - match mails that have been read (the \\SEEN flag is set)
     *                         SINCE "date" - match mails with Date: after "date"
     *                         SUBJECT "string" - match mails with "string" in the Subject:
     *                         TEXT "string" - match mails with text "string"
     *                         TO "string" - match mails with "string" in the To:
     *                         UNANSWERED - match mails that have not been answered
     *                         UNDELETED - match mails that are not deleted
     *                         UNFLAGGED - match mails that are not flagged
     *                         UNKEYWORD "string" - match mails that do not have the keyword "string"
     *                         UNSEEN - match mails which have not been read yet
     *
     * @return array Mails ids
     */
    public function searchMailbox($criteria = self::CRITERIA_ALL): array
    {
        $searchQuery = new ImapSearchQuery();

        if (preg_match('/'.self::CRITERIA_UID.' ((\d+):(\d+|\*))/', $criteria, $matches)) {
            $searchQuery->ids(ImapIdSet::fromSequenceString($matches[1]));
        } elseif (self::CRITERIA_UNSEEN === $criteria) {
            $searchQuery->flag('SEEN', false);
        } elseif (self::CRITERIA_SEEN === $criteria) {
            $searchQuery->flag('SEEN');
        } elseif (self::CRITERIA_ALL !== $criteria) {
            throw new MailboxException("Unsupported search criteria: {$criteria}");
        }

        try {
            return $this->getClient()->search($this->imapFolder, $searchQuery)->match->toArray();
        } catch (MailboxException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new MailboxException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Save mail body.
     *
     * @param string $filename
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function saveMail($mailId, $filename = 'email.eml'): bool
    {
        return imap_savebody($this->getImapStream(), $filename, $mailId, '', FT_UID);
    }

    /**
     * Marks mails listed in mailId for deletion.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function deleteMail($mailId): bool
    {
        return imap_delete($this->getImapStream(), $mailId, FT_UID);
    }

    /**
     * Move mail to another box.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function moveMail($mailId, $mailBox): bool
    {
        return imap_mail_move($this->getImapStream(), $mailId, $mailBox, CP_UID) && $this->expungeDeletedMails();
    }

    /**
     * Deletes all the mails marked for deletion by imap_delete(), imap_mail_move(), or imap_setflag_full().
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function expungeDeletedMails(): bool
    {
        return imap_expunge($this->getImapStream());
    }

    /**
     * Add the flag \Seen to a mail.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailAsRead($mailId): bool
    {
        return $this->setFlag([$mailId], '\\Seen');
    }

    /**
     * Remove the flag \Seen from a mail.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailAsUnread($mailId): bool
    {
        return $this->clearFlag([$mailId], '\\Seen');
    }

    /**
     * Add the flag \Flagged to a mail.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailAsImportant($mailId): bool
    {
        return $this->setFlag([$mailId], '\\Flagged');
    }

    /**
     * Add the flag \Seen to a mails.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailsAsRead(array $mailIds): bool
    {
        return $this->setFlag($mailIds, '\\Seen');
    }

    /**
     * Remove the flag \Seen from some mails.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailsAsUnread(array $mailIds): bool
    {
        return $this->clearFlag($mailIds, '\\Seen');
    }

    /**
     * Add the flag \Flagged to some mails.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function markMailsAsImportant(array $mailIds): bool
    {
        return $this->setFlag($mailIds, '\\Flagged');
    }

    /**
     * Causes a store to add the specified flag to the flags set for the mails in the specified sequence.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     *
     * @param string $flag which you can set are \Seen, \Answered, \Flagged, \Deleted, and \Draft as defined by RFC2060
     */
    public function setFlag(array $mailsIds, $flag): bool
    {
        return imap_setflag_full($this->getImapStream(), implode(',', $mailsIds), $flag, ST_UID);
    }

    /**
     * Cause a store to delete the specified flag to the flags set for the mails in the specified sequence.
     *
     * @param string $flag which you can set are \Seen, \Answered, \Flagged, \Deleted, and \Draft as defined by RFC2060
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function clearFlag(array $mailsIds, $flag): bool
    {
        return imap_clearflag_full($this->getImapStream(), implode(',', $mailsIds), $flag, ST_UID);
    }

    /**
     * Fetch mail headers for listed mails ids.
     *
     * Returns an array of objects describing one mail header each. The object will only define a property if it exists. The possible properties are:
     *  subject - the mails subject
     *  from - who sent it
     *  to - recipient
     *  date - when was it sent
     *  message_id - Mail-ID
     *  references - is a reference to this mail id
     *  in_reply_to - is a reply to this mail id
     *  size - size in bytes
     *  uid - UID the mail has in the mailbox
     *  msgno - mail sequence number in the mailbox
     *  recent - this mail is flagged as recent
     *  flagged - this mail is flagged
     *  answered - this mail is flagged as answered
     *  deleted - this mail is flagged for deletion
     *  seen - this mail is flagged as already read
     *  draft - this mail is flagged as being a draft
     *
     * @return array
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function getMailsInfo(array $mailsIds): array|false
    {
        $mails = imap_fetch_overview($this->getImapStream(), implode(',', $mailsIds), FT_UID);
        if (is_array($mails) && count($mails)) {
            foreach ($mails as &$mail) {
                if (isset($mail->subject)) {
                    $mail->subject = $this->decodeMimeStr($mail->subject);
                }
                if (isset($mail->from)) {
                    $mail->from = $this->decodeMimeStr($mail->from);
                }
                if (isset($mail->to)) {
                    $mail->to = $this->decodeMimeStr($mail->to);
                }
            }
        }

        return $mails;
    }

    /**
     * Get information about the current mailbox.
     *
     * Returns an object with following properties:
     *  Date - last change (current datetime)
     *  Driver - driver
     *  Mailbox - name of the mailbox
     *  Nmsgs - number of messages
     *  Recent - number of recent messages
     *  Unread - number of unread messages
     *  Deleted - number of deleted messages
     *  Size - mailbox size
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function getMailboxInfo(): \stdClass
    {
        return imap_mailboxmsginfo($this->getImapStream());
    }

    /**
     * Gets mails ids sorted by some criteria.
     *
     * Criteria can be one (and only one) of the following constants:
     *  SORTDATE - mail Date
     *  SORTARRIVAL - arrival date (default)
     *  SORTFROM - mailbox in first From address
     *  SORTSUBJECT - mail subject
     *  SORTTO - mailbox in first To address
     *  SORTCC - mailbox in first cc address
     *  SORTSIZE - size of mail in octets
     *
     * @param int  $criteria
     * @param bool $reverse
     *
     * @return array Mails ids
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function sortMails($criteria = SORTARRIVAL, $reverse = true): array|bool
    {
        return imap_sort($this->getImapStream(), $criteria, $reverse, SE_UID);
    }

    /**
     * Get mails count in mail box.
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function countMails(): int|bool
    {
        return imap_num_msg($this->getImapStream());
    }

    /**
     * Retrieve the quota settings per user.
     *
     * @return array - FALSE in the case of call failure
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    protected function getQuota(): array|bool
    {
        return imap_get_quotaroot($this->getImapStream(), 'INBOX');
    }

    /**
     * Return quota limit in KB.
     *
     * @return int - FALSE in the case of call failure
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function getQuotaLimit()
    {
        $quota = $this->getQuota();
        if (is_array($quota)) {
            $quota = $quota['STORAGE']['limit'];
        }

        return $quota;
    }

    /**
     * Return quota usage in KB.
     *
     * @return int - FALSE in the case of call failure
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    public function getQuotaUsage()
    {
        $quota = $this->getQuota();
        if (is_array($quota)) {
            $quota = $quota['STORAGE']['usage'];
        }

        return $quota;
    }

    /**
     * Get mail data.
     *
     * @param bool $markAsSeen
     */
    public function getMail($mailId, $markAsSeen = true): Message
    {
        $ids = new ImapIdSet([$mailId], false);

        $fetchQuery = new ImapFetchQuery();
        $fetchQuery->fullMsg(peek: !$markAsSeen);
        $fetchQuery->headerText(peek: !$markAsSeen);

        $fetchData = null;
        foreach ($this->getClient()->fetch($this->imapFolder, $ids, $fetchQuery) as $result) {
            $fetchData = $result;
            break;
        }

        if (!$fetchData) {
            throw new MailboxException("Mail ID {$mailId} not found");
        }

        $rawHeaders = (string) $fetchData->getHeaderText();
        $rawMessage = (string) $fetchData->getFullMsg();

        $headers = HeaderCollection::parse($rawHeaders);

        $mail       = new Message();
        $mail->id   = $mailId;

        $dateHeader = $headers->date();
        $mail->date = date('Y-m-d H:i:s', $dateHeader ? strtotime(preg_replace('/\(.*?\)/', '', $dateHeader->value())) : time());

        $subjectHeader = $headers->subject();
        $mail->subject = $subjectHeader ? Rfc2047::decode($subjectHeader->value()) : null;

        if ($from = $headers->from()) {
            $addresses = $this->parseAddressList($from->value());
            if ([] !== $addresses) {
                $firstFrom         = $addresses[0];
                $mail->fromName    = $firstFrom['personal'] ?? null;
                $mail->fromAddress = strtolower($firstFrom['email']);
            }
        }

        if ($to = $headers->get('To')) {
            $toAddresses = $this->parseAddressList($to->value());
            $toStrings   = [];
            foreach ($toAddresses as $addr) {
                $email              = strtolower($addr['email']);
                $name               = $addr['personal'] ?? null;
                $toStrings[]        = $name ? "{$name} <{$email}>" : $email;
                $mail->to[$email]   = $name;
            }
            $mail->toString = implode(', ', $toStrings);
        }

        if ($cc = $headers->get('Cc')) {
            foreach ($this->parseAddressList($cc->value()) as $addr) {
                $mail->cc[strtolower($addr['email'])] = $addr['personal'] ?? null;
            }
        }

        if ($replyTo = $headers->get('Reply-To')) {
            foreach ($this->parseAddressList($replyTo->value()) as $addr) {
                $mail->replyTo[strtolower($addr['email'])] = $addr['personal'] ?? null;
            }
        }

        if ($inReplyTo = $headers->get('In-Reply-To')) {
            $mail->inReplyTo = $inReplyTo->value();
        }

        if ($returnPath = $headers->get('Return-Path')) {
            $mail->returnPath = $returnPath->value();
        }

        if ($references = $headers->get('References')) {
            $mail->references = preg_split('/\s+/', $references->value(), -1, PREG_SPLIT_NO_EMPTY);
        }

        $this->parseBody($mail, $rawMessage);

        // Parse X headers
        $xHeaders = [];
        $tempArray = explode("\n", $rawHeaders);
        foreach ($tempArray as $line) {
            if (preg_match('/^X-(.*?): (.*?)$/is', trim($line), $matches)) {
                $xHeaders['x-'.strtolower($matches[1])] = $matches[2];
            }
        }
        $mail->xHeaders = $xHeaders;

        return $mail;
    }

    /**
     * @throws MailboxException
     */
    private function getClient(): ImapClient
    {
        if (null === $this->client) {
            try {
                $config = new ConnectionConfig(
                    $this->settings['host'],
                    (int) $this->settings['port'],
                    $this->mapEncryption($this->settings['encryption'] ?? ''),
                    15,
                );
                $credentials = new PasswordCredentials(
                    $this->settings['user'],
                    new PlainSecret($this->settings['password']),
                );
                $this->client = new ImapClient($config, $credentials);
                $this->client->login();
                $this->client->openMailbox($this->imapFolder, OpenMode::ReadWrite);
            } catch (\Exception $e) {
                $this->client = null;
                throw new MailboxException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $this->client;
    }

    private function mapEncryption(string $encryption): SecureMode
    {
        return match (strtolower(ltrim($encryption, '/'))) {
            '' => SecureMode::None,
            'tls', 'starttls', 'tls/novalidate-cert' => SecureMode::Tls,
            default => SecureMode::Ssl,
        };
    }

    private function parseBody(Message $mail, string $rawMessage): void
    {
        $mimePart = MimeParser::parse($rawMessage);

        $this->walkMimePart($mail, $mimePart);
    }

    private function walkMimePart(Message $mail, Part $part, bool $isDsn = false, bool $isFbl = false): void
    {
        $subtype = $part->subType();

        if ('multipart' === $part->primaryType()) {
            if ('report' === $subtype) {
                $reportType = strtolower($part->contentType()->param('report-type') ?? '');
                match ($reportType) {
                    'delivery-status'  => $isDsn = true,
                    'feedback-report'  => $isFbl = true,
                    default            => null,
                };
            }

            foreach ($part->children as $subPart) {
                $this->walkMimePart($mail, $subPart, $isDsn, $isFbl);
            }

            return;
        }

        $data = $part->bodyString();

        // Handle attachments
        $contentId   = $part->contentId();
        $filename    = $part->filename();
        $disposition = $part->contentDisposition()?->dispositionType();

        $isAttachment = ('attachment' === $disposition) || $contentId || $filename;

        // Not an attachment if it's a simple text body
        if ($isAttachment && !$contentId && !$filename && 'text' === $part->primaryType()) {
            $isAttachment = false;
        }

        if ($isAttachment && $filename) {
            if (isset($this->settings['use_attachments']) && $this->settings['use_attachments']) {
                $attachmentId     = $contentId ?: (mt_rand().mt_rand());
                $attachment       = new Attachment();
                $attachment->id   = $attachmentId;
                $attachment->name = $filename;
                if ($this->attachmentsDir) {
                    $replace = [
                        '/\s/'                    => '_',
                        '/[^0-9a-zа-яіїє_\.]/iu' => '',
                        '/_+/'                    => '_',
                        '/(^_)|(_$)/'             => '',
                    ];
                    $fileSysName = preg_replace(
                        '~[\\\\/]~',
                        '',
                        $mail->id.'_'.$attachmentId.'_'.preg_replace(array_keys($replace), $replace, $filename)
                    );
                    $attachment->filePath = $this->attachmentsDir.DIRECTORY_SEPARATOR.$fileSysName;
                    file_put_contents($attachment->filePath, $data);
                }
                $mail->addAttachment($attachment);
            }
        } else {
            if (!empty($data)) {
                $charset = $part->charset();
                if ($charset) {
                    $data = $this->convertStringEncoding($data, $charset, $this->serverEncoding);
                }

                switch ($part->primaryType()) {
                    case 'text':
                        match ($subtype) {
                            'plain' => $mail->textPlain .= $data,
                            default => $mail->textHtml .= $data,
                        };
                        break;
                    case 'message':
                        if ($isDsn || 'delivery-status' === $subtype) {
                            $mail->dsnReport = $data;
                        } elseif ($isFbl || 'feedback-report' === $subtype) {
                            $mail->fblReport = $data;
                        } else {
                            $mail->textPlain .= trim($data);
                        }
                        break;
                }

                if ($isDsn && 'delivery-status' === $subtype) {
                    $mail->dsnMessage = trim($data);
                } elseif ($isFbl && 'feedback-report' === $subtype) {
                    $mail->fblMessage = trim($data);
                }
            }
        }

        if ('message' === $part->primaryType() && 'rfc822' === $subtype) {
            foreach ($part->children as $subPart) {
                $this->walkMimePart($mail, $subPart, $isDsn, $isFbl);
            }
        }
    }

    private function parseAddressList(string $value): array
    {
        $addresses = [];
        $parser    = new Rfc822Parser();
        $parsed    = $parser->parseAddressList($value);

        foreach ($parsed as $addr) {
            if (!empty($addr->mailbox) && !empty($addr->host)) {
                $addresses[] = [
                    'email'    => $addr->mailbox.'@'.$addr->host,
                    'personal' => !empty($addr->personal) ? Rfc2047::decode($addr->personal) : null,
                ];
            }
        }

        return $addresses;
    }

    /**
     * @param string $charset
     *
     * @deprecated since 7.x, to be removed in 8.0. No replacement; IMAP access now uses horde/imap_client.
     */
    protected function decodeMimeStr($string, $charset = 'utf-8'): string
    {
        $newString = '';
        $elements  = imap_mime_header_decode($string);
        $counter = count($elements);
        for ($i = 0; $i < $counter; ++$i) {
            if ('default' == $elements[$i]->charset) {
                $elements[$i]->charset = 'iso-8859-1';
            }
            $newString .= $this->convertStringEncoding($elements[$i]->text, $elements[$i]->charset, $charset);
        }

        return $newString;
    }

    /**
     * @return string Converted string if conversion was successful, or the original string if not
     */
    protected function convertStringEncoding($string, $fromEncoding, $toEncoding)
    {
        $convertedString = null;
        if ($string && $fromEncoding != $toEncoding) {
            $convertedString = @iconv($fromEncoding, $toEncoding.'//IGNORE', $string);
            if (!$convertedString && extension_loaded('mbstring')) {
                $convertedString = @mb_convert_encoding($string, $toEncoding, $fromEncoding);
            }
        }

        return $convertedString ?: $string;
    }

    protected function disconnect(): void
    {
        if (null !== $this->client) {
            try {
                $this->client->logout();
            } catch (\Exception) {
            }
            $this->client = null;
        }

        if ($this->imapStream) {
            // Prevent these from throwing notices such as "SECURITY PROBLEM: insecure server advertised"
            imap_errors();
            imap_alerts();

            @imap_close($this->imapStream, CL_EXPUNGE);
            $this->imapStream = null;
        }
    }

    private function createAttachmentsDir(PathsHelper $pathsHelper): void
    {
        if (!isset($this->settings['use_attachments']) || !$this->settings['use_attachments']) {
            return;
        }

        $this->attachmentsDir = $pathsHelper->getSystemPath('tmp', true);

        if (!file_exists($this->attachmentsDir)) {
            mkdir($this->attachmentsDir);
        }
        $this->attachmentsDir .= '/attachments';

        if (!file_exists($this->attachmentsDir)) {
            mkdir($this->attachmentsDir);
        }
    }

    /**
     * Disconnect on destruct.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
