<?php

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;
use Mautic\LeadBundle\Entity\Lead;

class DynamicContentLeadData extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var DynamicContent|null
     */
    private $dynamicContent;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dataAdded;

    /**
     * @var string
     */
    private $slot;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content_lead_data')
            ->setCustomRepositoryClass(DynamicContentLeadDataRepository::class);

        $builder->addIdColumns(false, false);

        $builder->addDateAdded(true);

        $builder->addLead();

        $builder->createManyToOne('dynamicContent', 'DynamicContent')
            ->inversedBy('id')
            ->addJoinColumn('dynamic_content_id', 'id', true, false, 'CASCADE')
            ->build();

        $builder->createField('slot', 'text')
            ->columnName('slot')
            ->build();
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return DynamicContent|null
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param DynamicContent $dynamicContent
     */
    public function setDynamicContent($dynamicContent): static
    {
        $this->dynamicContent = $dynamicContent;

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
     * @param Lead $lead
     */
    public function setLead($lead): static
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDataAdded()
    {
        return $this->dataAdded;
    }

    /**
     * @param \DateTime $dataAdded
     */
    public function setDataAdded($dataAdded): static
    {
        $this->dataAdded = $dataAdded;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlot()
    {
        return $this->slot;
    }

    /**
     * @param string $slot
     */
    public function setSlot($slot): static
    {
        $this->slot = $slot;

        return $this;
    }
}
