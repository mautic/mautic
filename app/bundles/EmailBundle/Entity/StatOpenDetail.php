<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class StatOpenDetail
{
    public const TABLE_NAME = 'email_stats_open_details';

    public const BOUNCES_KEY = 'bounces';

    public const ROW_ID_KEY = '_id';

    private ?string $id = null;

    private ?Stat $stat = null;

    private ?\DateTimeInterface $dateSent = null;

    private ?array $openDetail = [];

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(StatOpenDetailRepository::class)
            ->addIndex(['date_sent'], 'email_date_sent');

        $builder->addBigIntIdField();

        $builder->createManyToOne('stat', 'Stat')
            ->addJoinColumn('stat_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('dateSent', 'datetime')
            ->columnName('date_sent')
            ->build();

        $builder->createField('openDetail', 'array')
            ->columnName('open_detail')
            ->length(65535)
            ->nullable()
            ->build();
    }

    /**
     * Folds one child row's stored entry into a running `Stat::getOpenDetails()`-shaped map.
     *
     * Each row holds exactly one entry, so this is a flat, one-shot check rather than a loop:
     * a row either carries the reserved 'bounces' key (one bounce entry, wrapped once) or it
     * is a bare open entry.
     *
     * @param array<int|string,mixed> $openDetails
     * @param array<string,mixed>     $openDetail
     *
     * @return array<int|string,mixed>
     */
    public static function mergeOpenDetail(array $openDetails, array $openDetail, ?string $id): array
    {
        if (isset($openDetail[self::BOUNCES_KEY])) {
            $entry                             = $openDetail[self::BOUNCES_KEY][0];
            $entry[self::ROW_ID_KEY]           = $id;
            $openDetails[self::BOUNCES_KEY][]  = $entry;

            return $openDetails;
        }

        $openDetail[self::ROW_ID_KEY] = $id;
        $openDetails[]                = $openDetail;

        return $openDetails;
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('stat')
            ->addProperties(
                [
                    'stat',
                    'dateSent',
                    'openDetail',
                ]
            )
            ->build();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getStat(): ?Stat
    {
        return $this->stat;
    }

    public function setStat(?Stat $stat): void
    {
        $this->stat = $stat;
    }

    public function getDateSent(): ?\DateTimeInterface
    {
        return $this->dateSent;
    }

    public function setDateSent(?\DateTimeInterface $dateSent): void
    {
        $dateSent       = $this->toDateTime($dateSent);
        $this->dateSent = $dateSent;
    }

    /**
     * @return array
     */
    public function getOpenDetail()
    {
        return $this->openDetail ?? [];
    }

    public function setOpenDetail(array $openDetail): self
    {
        $this->openDetail = $openDetail;

        return $this;
    }

    /**
     * @param \DateTime|\DateTimeImmutable|null $dateTime
     */
    private function toDateTime($dateTime): ?\DateTime
    {
        return $dateTime instanceof \DateTimeImmutable ? \DateTime::createFromImmutable($dateTime) : $dateTime;
    }
}
