<?php

namespace Mautic\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\LeadBundle\Entity\Lead;

class PushID
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead|null
     */
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

        $builder->setTable('push_ids')
            ->setCustomRepositoryClass(PushIDRepository::class);

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->createField('pushID', 'string')
            ->columnName('push_id')
            ->nullable(false)
            ->build();

        $builder->createManyToOne('lead', Lead::class)
            ->addJoinColumn('lead_id', 'id', true, false, 'SET NULL')
            ->inversedBy('pushIds')
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
     *
     * @return $this
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

    /**
     * @return $this
     */
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
     *
     * @return $this
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

    /**
     * @return $this
     */
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
     *
     * @return $this
     */
    public function setMobile($mobile): static
    {
        $this->mobile = $mobile;

        return $this;
    }
}
