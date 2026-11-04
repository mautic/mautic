<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class LeadListTranslation
{
    public const TABLE_NAME = 'lead_list_translations';

    private ?int $id = null;

    public function __construct(
        private LeadList $leadList,
        private string $locale,
        private string $publicName,
    ) {
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(LeadListTranslationRepository::class)
            ->addUniqueConstraint(['leadlist_id', 'locale'], 'lead_list_translation_locale');

        $builder->addId();

        $builder->createManyToOne('leadList', LeadList::class)
            ->addJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('locale', 'string')
            ->length(16)
            ->build();

        $builder->createField('publicName', 'string')
            ->columnName('public_name')
            ->length(255)
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeadList(): LeadList
    {
        return $this->leadList;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getPublicName(): string
    {
        return $this->publicName;
    }

    public function setPublicName(string $publicName): void
    {
        $this->publicName = $publicName;
    }
}
