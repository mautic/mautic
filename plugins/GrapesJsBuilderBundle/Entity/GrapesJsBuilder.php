<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\EmailBundle\Entity\Email;

#[ORM\Entity(repositoryClass: GrapesJsBuilderRepository::class)]
#[ORM\Table(name: 'bundle_grapesjsbuilder')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class GrapesJsBuilder
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
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
    #[ORM\Column(name: 'custom_mjml', type: Types::TEXT, nullable: true)]
    private $customMjml;

    #[ORM\Column(name: 'draft_custom_mjml', type: Types::TEXT, nullable: true)]
    private ?string $draftCustomMjml = null;

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
