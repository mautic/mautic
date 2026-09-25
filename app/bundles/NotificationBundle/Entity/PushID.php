<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: PushIDRepository::class)]
#[ORM\Table(name: 'push_ids')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PushID
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead|null
     */
    #[ORM\ManyToOne(targetEntity: Lead::class, inversedBy: 'pushIds')]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private $lead;

    /**
     * @var string
     */
    private $pushID;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @var bool
     */
    private $mobile;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('pushID', 'string')
            ->columnName('push_id')
            ->nullable(false)
            ->build();

        $builder->createField('enabled', 'boolean')->build();
        $builder->createField('mobile', 'boolean')->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Lead|null
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPushID()
    {
        return $this->pushID;
    }

    /**
     * @param string $pushID
     */
    public function setPushID($pushID): static
    {
        $this->pushID = $pushID;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    public function setMobile(bool $mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }
}
