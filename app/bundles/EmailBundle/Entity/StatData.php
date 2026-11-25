<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Type\ArrayType;

/**
 * Per-send data of an email stat that can be deleted by the opt-in email stats cleanup.
 */
#[ORM\Entity]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\Index(name: 'email_date_sent', columns: ['date_sent'])]
class StatData
{
    public const string TABLE_NAME = 'email_stats_data';

    #[ORM\Column(name: 'date_sent', type: 'datetime')]
    private \DateTimeInterface $dateSent;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: ArrayType::ARRAY, nullable: true)]
    private ?array $tokens = null;

    public function __construct(
        #[ORM\Id]
        #[ORM\OneToOne(targetEntity: Stat::class, inversedBy: 'data')]
        #[ORM\JoinColumn(name: 'stat_id', nullable: false, onDelete: 'CASCADE')]
        private readonly Stat $stat,
    ) {
        $this->dateSent = $stat->getDateSent() ?? new \DateTime();
    }

    public function getStat(): Stat
    {
        return $this->stat;
    }

    public function getDateSent(): \DateTimeInterface
    {
        return $this->dateSent;
    }

    public function setDateSent(\DateTimeInterface $dateSent): void
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTokens(): array
    {
        return $this->tokens ?? [];
    }

    /**
     * @param array<string, mixed> $tokens
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }
}
