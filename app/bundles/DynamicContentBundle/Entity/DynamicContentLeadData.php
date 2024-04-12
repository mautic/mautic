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
            ->setCustomRepositoryClass(\Mautic\DynamicContentBundle\Entity\DynamicContentLeadDataRepository::class);

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return DynamicContentLeadData
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return DynamicContent
     */
    public function getDynamicContent()
    {
        return $this->dynamicContent;
    }

    /**
     * @param DynamicContent $dynamicContent
     *
     * @return DynamicContentLeadData
     */
    public function setDynamicContent($dynamicContent)
    {
        $this->dynamicContent = $dynamicContent;

        return $this;
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param Lead $lead
     *
     * @return DynamicContentLeadData
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDataAdded()
    {
        return $this->dataAdded;
    }

    /**
     * @param \DateTime $dataAdded
     *
     * @return DynamicContentLeadData
     */
    public function setDataAdded($dataAdded)
    {
        $this->dataAdded = $dataAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlot()
    {
        return $this->slot;
    }

    /**
     * @param string $slot
     *
     * @return DynamicContentLeadData
     */
    public function setSlot($slot)
    {
        $this->slot = $slot;

        return $this;
    }
}
