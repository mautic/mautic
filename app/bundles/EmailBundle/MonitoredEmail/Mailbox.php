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

    private $imapFolder = 'INBOX';

    private string $serverEncoding = 'UTF-8';

    private $attachmentsDir;

    private $settings;

    private $mailboxes;

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
    }

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
            $this->disconnect();
        } else {
            throw new MailboxException($key.' not found');
        }
    }

    public function setMailboxSettings(array $settings): void
    {
        $this->settings = array_merge($this->settings, $settings);
        $this->disconnect();
    }

    /**
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

            $settings['folder']    = $this->mailboxes[$key]['folder'];
            $settings['imap_path'] = $this->buildImapPath($settings);
        } else {
            throw new MailboxException($key.' not found');
        }

        return $settings;
    }

    /**
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
        $client = $this->getClient();
        $searchQuery = new ImapSearchQuery();

        if (preg_match('/'.self::CRITERIA_UID.' ((\d+):(\d+|\*))/', $criteria, $matches)) {
            $searchQuery->ids(ImapIdSet::fromSequenceString($matches[1]));
        } elseif (self::CRITERIA_UNSEEN === $criteria) {
            $searchQuery->flag('SEEN', false);
        } elseif (self::CRITERIA_ALL !== $criteria) {
            $searchQuery->flag('SEEN', false);
        }

        try {
            $results = $client->search(
                $this->imapFolder,
                $searchQuery,
            );

            return $results->match->toArray();
        } catch (\Exception) {
            return [];
        }
    }

    /**
     * @param bool $markAsSeen
     */
    public function getMail($mailId, $markAsSeen = true): Message
    {
        $client = $this->getClient();
        $ids    = new ImapIdSet([$mailId], false);

        $fetchQuery = new ImapFetchQuery();
        $fetchQuery->fullMsg(peek: !$markAsSeen);
        $fetchQuery->headerText(peek: !$markAsSeen);

        $fetchData = null;
        foreach ($client->fetch($this->imapFolder, $ids, $fetchQuery) as $result) {
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
        $mail->subject = $subjectHeader ? $this->decodeMimeStr($subjectHeader->value()) : null;

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

    public function __destruct()
    {
        $this->disconnect();
    }

    private function disconnect(): void
    {
        if (null !== $this->client) {
            try {
                $this->client->logout();
            } catch (\Exception) {
            }
            $this->client = null;
        }
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
            'tls', 'starttls' => SecureMode::Tls,
            default    => SecureMode::Ssl,
        };
    }

    private function buildImapPath(array $settings): string
    {
        $encryption = $settings['encryption'] ?? '';
        $path       = "{{$settings['host']}:{$settings['port']}/imap{$encryption}}";

        if (isset($settings['folder'])) {
            $path .= $settings['folder'];
        }

        return $path;
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
                    'personal' => !empty($addr->personal) ? $this->decodeMimeStr($addr->personal) : null,
                ];
            }
        }

        return $addresses;
    }

    private function decodeMimeStr(string $string): string
    {
        return Rfc2047::decode($string);
    }

    /**
     * @return string Converted string if conversion was successful, or the original string if not
     */
    private function convertStringEncoding(string $string, string $fromEncoding, string $toEncoding)
    {
        $convertedString = null;
        if ($string && $fromEncoding !== $toEncoding) {
            $convertedString = @iconv($fromEncoding, $toEncoding.'//IGNORE', $string);
            if (!$convertedString && extension_loaded('mbstring')) {
                $convertedString = @mb_convert_encoding($string, $toEncoding, $fromEncoding);
            }
        }

        return $convertedString ?: $string;
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
}
