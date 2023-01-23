<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\Company;

class LeagueCompanyScore extends FormEntity
{
    public const TABLE_NAME = 'league_company_score';

    private Company $company;
    private League $league;
    private int $score;

    /**
     * @param ORM\ClassMetadata<LeagueCompanyScore> $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass('Mautic\PointBundle\Entity\LeagueCompanyScoreRepository');

        $builder->createManyToOne('company', 'Mautic\LeadBundle\Entity\Company')
            ->isPrimaryKey()
            ->addJoinColumn('company_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createManyToOne('league', 'Mautic\PointBundle\Entity\League')
            ->isPrimaryKey()
            ->addJoinColumn('league_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('score', 'integer')
            ->build();
    }

    public function getCompany(): Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): void
    {
        $this->company = $company;
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
