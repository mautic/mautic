<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Tag as BaseTag;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'lead_tags')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Tag extends BaseTag
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder
            ->setEmbeddable();
    }
}
