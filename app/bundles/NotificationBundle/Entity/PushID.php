<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\LeadBundle\Entity\Lead;

#[ORM\Entity(repositoryClass: PushIDRepository::class)]
#[ORM\Table(name: 'push_ids')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PushID
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private $id;

    #[ORM\ManyToOne(targetEntity: Lead::class, inversedBy: 'pushIds')]
    #[ORM\JoinColumn(name: 'lead_id', onDelete: 'SET NULL')]
    private ?\Mautic\LeadBundle\Entity\Lead $lead = null;

    /**
     * @var string
     */
    #[ORM\Column(name: 'push_id', type: 'string', length: 191)]
    private $pushID;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $enabled;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    private $mobile;

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

    public function setEnabled($enabled): static
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

    /**
     * @param bool $mobile
     */
    public function setMobile($mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }
}
