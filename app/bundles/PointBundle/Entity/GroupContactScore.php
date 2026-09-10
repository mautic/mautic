<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: GroupContactScoreRepository::class)]
#[ORM\Table(name: self::TABLE_NAME)]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class GroupContactScore extends CommonEntity
{
    public const TABLE_NAME = 'point_group_contact_score';

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \Mautic\LeadBundle\Entity\Lead::class, inversedBy: 'groupScores')]
    #[ORM\JoinColumn(name: 'contact_id', nullable: false, onDelete: 'CASCADE')]
    private Lead $contact;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(name: 'group_id', onDelete: 'CASCADE')]
    private Group $group;

    #[ORM\Column(type: Types::INTEGER)]
    private int $score = 0;

    public function __construct()
    {
        $this->contact = new Lead();
        $this->group   = new Group();
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('groupContactScore')
            ->addListProperties(
                [
                    'score',
                    'group',
                ]
            )
            ->addProperties(
                [
                    'score',
                    'group',
                ]
            )
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

    public function getGroup(): Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): void
    {
        $this->group = $group;
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
