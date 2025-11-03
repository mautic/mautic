<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class StatOpenDetail
{
    public const TABLE_NAME = 'email_stats_open_details';

    private ?int $id = null;

    private ?Stat $stat;

    private ?\DateTimeInterface $dateSent = null;

    private array $openDetail = [];

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

        $builder->addNullableField('openDetail', 'array', 'open_detail');
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

    public function getId(): ?int
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
        return $this->openDetail;
    }

    /**
     * @return Stat
     */
    public function setOpenDetail(array $openDetail)
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
