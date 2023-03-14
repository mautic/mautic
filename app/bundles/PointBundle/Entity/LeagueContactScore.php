<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

class LeagueContactScore extends CommonEntity
{
    public const TABLE_NAME = 'league_contact_score';

    private Lead $contact;
    private League $league;
    private int $score;

    /**
     * @param ORM\ClassMetadata<LeagueContactScore> $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass('Mautic\PointBundle\Entity\LeagueContactScoreRepository');

        $builder->addContact(false, 'CASCADE', true, 'league_score');

        $builder->createManyToOne('league', 'Mautic\PointBundle\Entity\League')
            ->isPrimaryKey()
            ->addJoinColumn('league_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('score', 'integer')
            ->build();
    }

    public function getContact(): Lead
    {
        return $this->contact;
    }

    public function setContact(Lead $contact): void
    {
        $this->contact = $contact;
    }

    public function getLeague(): League
    {
        return $this->league;
    }

    public function setLeague(League $league): void
    {
        $this->league = $league;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): void
    {
        $this->score = $score;
    }
}
