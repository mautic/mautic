<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\PageBundle\Entity\Redirect;

/**
 * One request to an email's honeypot link, tracked link or tracking pixel, kept to study how link scanners behave.
 * It stores no contact ID and no IP address: sends are told apart by a keyed hash, IPs by a keyed hash and a prefix.
 */
class ScannerDiagnostic
{
    public const TABLE_NAME = 'email_scanner_diagnostics';
    public const EVENT_HONEYPOT = 'honeypot';
    public const EVENT_CLICK = 'click';
    public const EVENT_OPEN = 'open';
    public const INDEX_EMAIL_SEND_DATE = 'scanner_diag_email_send_date';
    public const INDEX_DATE_ADDED = 'scanner_diag_date_added';
    private ?string $id = null;

    public function __construct(
        private readonly \DateTimeImmutable $dateAdded,
        private readonly string $eventType,
        private readonly ?Email $email,
        private readonly ?string $sendKey,
        private readonly ?Redirect $redirect,
        private readonly ?int $secondsAfterSend,
        private readonly string $ipHash,
        private readonly ?string $ipNetwork,
        private readonly ?string $userAgent,
        private readonly array $headers,
        private readonly bool $isHttps,
        private readonly ?bool $botRatioFlagged,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(ScannerDiagnosticRepository::class)
            ->addIndex(['email_id', 'send_key', 'date_added'], self::INDEX_EMAIL_SEND_DATE)
            ->addIndex(['date_added'], self::INDEX_DATE_ADDED);

        $builder->addBigIntIdField();

        $builder->addNamedField('dateAdded', Types::DATETIME_IMMUTABLE, 'date_added');

        $builder->createField('eventType', Types::STRING)
            ->columnName('event_type')
            ->length(16)
            ->build();

        $builder->createManyToOne('email', Email::class)
            ->addJoinColumn('email_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('sendKey', Types::STRING)
            ->columnName('send_key')
            ->length(16)
            ->nullable()
            ->build();

        $builder->createManyToOne('redirect', Redirect::class)
            ->addJoinColumn('redirect_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->addNamedField('secondsAfterSend', Types::INTEGER, 'seconds_after_send', true);

        $builder->createField('ipHash', Types::STRING)
            ->columnName('ip_hash')
            ->length(16)
            ->build();

        $builder->createField('ipNetwork', Types::STRING)
            ->columnName('ip_network')
            ->length(43)
            ->nullable()
            ->build();

        $builder->createField('userAgent', Types::STRING)
            ->columnName('user_agent')
            ->length(255)
            ->nullable()
            ->build();

        $builder->addField('headers', Types::JSON);

        $builder->addNamedField('isHttps', Types::BOOLEAN, 'is_https');

        $builder->addNamedField('botRatioFlagged', Types::BOOLEAN, 'bot_ratio_flagged', true);
    }

    public function getId(): ?int
    {
        return null === $this->id ? null : (int) $this->id;
    }

    public function getDateAdded(): \DateTimeImmutable
    {
        return $this->dateAdded;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function getSendKey(): ?string
    {
        return $this->sendKey;
    }

    public function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }

    public function getSecondsAfterSend(): ?int
    {
        return $this->secondsAfterSend;
    }

    public function getIpHash(): string
    {
        return $this->ipHash;
    }

    public function getIpNetwork(): ?string
    {
        return $this->ipNetwork;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function isHttps(): bool
    {
        return $this->isHttps;
    }

    public function isBotRatioFlagged(): ?bool
    {
        return $this->botRatioFlagged;
    }
}
