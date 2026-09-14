<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\EmailBundle\Entity\Email;

#[ORM\Entity]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class GrapesJsBuilder
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var Email|null
     */
    #[ORM\ManyToOne(targetEntity: Email::class)]
    #[ORM\JoinColumn(name: 'email_id', onDelete: 'CASCADE')]
    protected $email;

    /**
     * @var string|null
     */
    private $customMjml;

    private ?string $draftCustomMjml = null;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('bundle_grapesjsbuilder')
            ->setCustomRepositoryClass(GrapesJsBuilderRepository::class)
            ->addNamedField('customMjml', Types::TEXT, 'custom_mjml', true)
            ->addNamedField('draftCustomMjml', Types::TEXT, 'draft_custom_mjml', true)
            ->addId();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Email
     */
    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail(Email $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCustomMjml()
    {
        return $this->customMjml;
    }

    /**
     * @param string $customMjml
     */
    public function setCustomMjml($customMjml): static
    {
        $this->customMjml = $customMjml;

        return $this;
    }

    public function getDraftCustomMjml(): ?string
    {
        return $this->draftCustomMjml;
    }

    public function setDraftCustomMjml(?string $draftCustomMjml): void
    {
        $this->draftCustomMjml = $draftCustomMjml;
    }
}
