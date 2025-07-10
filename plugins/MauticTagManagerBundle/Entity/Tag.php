<?php

namespace MauticPlugin\MauticTagManagerBundle\Entity;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Tag as BaseTag;

class Tag extends BaseTag
{
    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('lead_tags')
            ->setEmbeddable()
            ->setCustomRepositoryClass(TagRepository::class);
    }
}
