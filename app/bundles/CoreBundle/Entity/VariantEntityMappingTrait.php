<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Model\AbTest\AbTestSettingsService;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Doctrine-mapped variant properties for entities using VariantEntityTrait.
 *
 * @template TEntity of VariantEntityInterface
 */
trait VariantEntityMappingTrait
{
    /**
     * @var Collection<int, TEntity>
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'variantParent', cascade: ['persist'], indexBy: 'id')]
    #[ORM\OrderBy(['isPublished' => 'DESC'])]
    private $variantChildren;

    /**
     * @var TEntity|null
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'variantChildren')]
    #[ORM\JoinColumn(name: 'variant_parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private $variantParent;

    /**
     * @var array<mixed>|null
     */
    #[Groups(['email:read', 'email:write', 'download:read'])]
    #[ORM\Column(name: 'variant_settings', type: 'array', nullable: true)]
    private $variantSettings = ['totalWeight' => AbTestSettingsService::DEFAULT_AB_WEIGHT, 'enableAbTest' => false];

    #[Groups(['email:read', 'email:write', 'download:read'])]
    #[ORM\Column(name: 'variant_start_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $variantStartDate = null;
}
