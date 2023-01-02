<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\Lead;

class LeagueLeadScore extends FormEntity
{
    private Lead $contact;
    private League $league;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('league_score')
            ->setCustomRepositoryClass('Mautic\PointBundle\Entity\LeagueLeadScoreRepository');

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
}
