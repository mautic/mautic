<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Transport;

use Horde_Imap_Client as Imap;
use Horde_Imap_Client_Fetch_Query as FetchQuery;
use Horde_Imap_Client_Ids as Ids;
use Horde_Imap_Client_Socket as Client;
use Mautic\EmailBundle\Exception\MailboxException;
use Mautic\EmailBundle\MonitoredEmail\Message;

/** @internal The fallback used only when ext-imap is unavailable. */
final readonly class LibraryMailbox
{
    private Client $client;

    /** @param array<string, mixed> $settings */
    public function __construct(private array $settings, private ?string $attachmentsDir)
    {
        $encryption = $settings['encryption'] ?? (!empty($settings['ssl']) ? '/ssl' : '');
        $flags      = array_filter(explode('/', strtolower($encryption)));
        if (array_diff($flags, ['ssl', 'tls', 'notls', 'validate-cert', 'novalidate-cert'])) {
            throw new MailboxException('Unsupported IMAP connection flags; install ext-imap for native connection options.');
        }
        $secure = match (true) {
            in_array('ssl', $flags, true)   => 'ssl',
            in_array('tls', $flags, true)   => 'tls',
            in_array('notls', $flags, true) => false,
            default                       => true,
        };
        $validate = !in_array('novalidate-cert', $flags, true);
        $this->client = new Client([
            'hostspec'     => $settings['host'],
            'port'         => (int) $settings['port'],
            'username'     => $settings['user'],
            'password'     => $settings['password'],
            'secure'       => $secure,
            'timeout'      => 15,
            'read_timeout' => 15,
            'context'      => ['ssl' => ['verify_peer' => $validate, 'verify_peer_name' => $validate]],
        ]);
    }

    public function switchFolder(string $folder): void
    {
        $this->client->openMailbox($folder, Imap::OPEN_READWRITE);
    }

    public function disconnect(): void
    {
        $this->client->close(['expunge' => true]);
        $this->client->logout();
    }

    public function createMailbox(string $folder): bool
    {
        $this->client->createMailbox($folder);

        return true;
    }

    /** @return list<string> */
    public function getListingFolders(): array
    {
        return array_map(strval(...), $this->client->listMailboxes('*', Imap::MBOX_ALL, ['flat' => true]));
    }

    public function statusMailbox(string $folder): \stdClass
    {
        return (object) $this->client->status($folder, Imap::STATUS_ALL | Imap::STATUS_FORCE_REFRESH);
    }

    public function checkMailbox(string $folder): \stdClass
    {
        $status = $this->statusMailbox($folder);

        return (object) ['Date' => date(DATE_RFC2822), 'Driver' => 'imap', 'Mailbox' => $folder, 'Nmsgs' => $status->messages, 'Recent' => $status->recent];
    }

    public function countMails(string $folder): int
    {
        return $this->statusMailbox($folder)->messages;
    }

    /** @return list<int> */
    public function searchMailbox(string $folder, string $criteria = 'ALL'): array
    {
        $result = $this->client->search($folder, new SearchQuery($criteria));

        return array_values($result['match']->ids);
    }

    public function saveMail(string $folder, int $id, string $filename): bool
    {
        return false !== file_put_contents($filename, $this->rawMessage($folder, $id, true));
    }

    public function deleteMail(string $folder, int|string $id): bool
    {
        return $this->setFlag($folder, [$id], '\\Deleted');
    }

    public function moveMail(string $folder, int|string $id, string $destination): bool
    {
        $this->client->copy($folder, $destination, ['ids' => new Ids($id)]);
        $this->deleteMail($folder, $id);

        return $this->expungeDeletedMails($folder);
    }

    public function expungeDeletedMails(string $folder): bool
    {
        $this->client->expunge($folder);

        return true;
    }

    /** @param list<int|string> $ids */
    public function setFlag(string $folder, array $ids, string $flag): bool
    {
        $this->client->store($folder, ['ids' => new Ids($ids), 'add' => preg_split('/\s+/', trim($flag))]);

        return true;
    }

    /** @param list<int|string> $ids */
    public function clearFlag(string $folder, array $ids, string $flag): bool
    {
        $this->client->store($folder, ['ids' => new Ids($ids), 'remove' => preg_split('/\s+/', trim($flag))]);

        return true;
    }

    public function getMail(string $folder, int $id, bool $markAsSeen = true): Message
    {
        return (new MessageParser())->parse($this->rawMessage($folder, $id, $markAsSeen), $id, !empty($this->settings['use_attachments']), $this->attachmentsDir);
    }

    /** @param list<int> $ids
     * @return list<\stdClass>
     */
    public function getMailsInfo(string $folder, array $ids): array
    {
        if ([] === $ids) {
            return [];
        }
        $query = new FetchQuery();
        $query->headerText(['peek' => true]);
        $query->flags();
        $query->size();
        $query->uid();
        $query->seq();
        $query->imapDate();
        $result = [];
        foreach ($this->client->fetch($folder, $query, ['ids' => new Ids($ids)]) as $data) {
            $mime = \ZBateson\MailMimeParser\Message::from($data->getHeaderText(), false);
            $info = (object) ['uid' => $data->getUid(), 'msgno' => $data->getSeq(), 'size' => $data->getSize(), 'udate' => $data->getImapDate()->getTimestamp()];
            foreach (['subject', 'from', 'to', 'cc', 'date', 'message-id', 'references', 'in-reply-to'] as $header) {
                if (null !== $value = $mime->getHeader($header)?->getRawValue()) {
                    $info->{str_replace('-', '_', $header)} = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') ?: $value;
                }
            }
            foreach (['recent', 'flagged', 'answered', 'deleted', 'seen', 'draft'] as $flag) {
                $info->{$flag} = (int) in_array('\\'.$flag, $data->getFlags(), true);
            }
            $result[] = $info;
        }

        return $result;
    }

    public function getMailboxInfo(string $folder): \stdClass
    {
        $info          = $this->checkMailbox($folder);
        $info->Unread  = $this->statusMailbox($folder)->unseen;
        $info->Deleted = count($this->searchMailbox($folder, 'DELETED'));
        $info->Size    = array_sum(array_column($this->getMailsInfo($folder, $this->searchMailbox($folder)), 'size'));

        return $info;
    }

    /** @return list<int> */
    public function sortMails(string $folder, int $criteria = 1, bool $reverse = true): array
    {
        // ext-imap's SORT* values, without requiring its global constants.
        $sort = [Imap::SORT_DATE, Imap::SORT_ARRIVAL, Imap::SORT_FROM, Imap::SORT_SUBJECT, Imap::SORT_TO, Imap::SORT_CC, Imap::SORT_SIZE][$criteria] ?? null;
        if (null === $sort) {
            throw new \InvalidArgumentException('Invalid IMAP sort criterion.');
        }
        $result = $this->client->search($folder, null, ['sort' => $reverse ? [Imap::SORT_REVERSE, $sort] : [$sort]]);

        return array_values($result['match']->ids);
    }

    /** @return array<string, array{usage: int, limit: int}>|false */
    public function getQuota(): array|false
    {
        try {
            $quotas = $this->client->getQuotaRoot('INBOX');

            return reset($quotas);
        } catch (\Horde_Imap_Client_Exception_NoSupportExtension) {
            return false;
        }
    }

    private function rawMessage(string $folder, int $id, bool $markAsSeen): string
    {
        $query = new FetchQuery();
        $query->fullText(['peek' => !$markAsSeen]);
        foreach ($this->client->fetch($folder, $query, ['ids' => new Ids([$id]), 'exists' => true]) as $data) {
            return $data->getFullMsg();
        }

        throw new MailboxException('The requested IMAP message no longer exists.');
    }
}
