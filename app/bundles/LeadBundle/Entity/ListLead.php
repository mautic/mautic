<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class ListLead
{
    /**
     * @var string
     */
    public const TABLE_NAME = 'lead_lists_leads';

    /**
     * @var LeadList
     **/
    private $list;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var \DateTimeInterface
     */
    private $dateAdded;

    /**
     * @var bool
     */
    private $manuallyRemoved = false;

    /**
     * @var bool
     */
    private $manuallyAdded = false;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_lists_leads')
            ->setCustomRepositoryClass(ListLeadRepository::class);

        $builder->createManyToOne('list', 'LeadList')
            ->isPrimaryKey()
            ->inversedBy('leads')
            ->addJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addLead(false, 'CASCADE', true);

        $builder->addDateAdded();

        $builder->createField('manuallyRemoved', 'boolean')
            ->columnName('manually_removed')
            ->build();

        $builder->createField('manuallyAdded', 'boolean')
            ->columnName('manually_added')
            ->build();

        $builder->addIndex(['manually_removed'], 'manually_removed');
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $date
     */
    public function setDateAdded($date): void
    {
        $this->dateAdded = $date;
    }

    /**
     * @return mixed
     */
    public function getLead()
    {
        return $this->lead;
    }

    /**
     * @param mixed $lead
     */
    public function setLead($lead): void
    {
        $this->lead = $lead;
    }

    /**
     * @return LeadList
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * @param LeadList $leadList
     */
    public function setList($leadList): void
    {
        $this->list = $leadList;
    }

    /**
     * @return bool
     */
    public function getManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @param bool $manuallyRemoved
     */
    public function setManuallyRemoved($manuallyRemoved): void
    {
        $this->manuallyRemoved = $manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function wasManuallyRemoved()
    {
        return $this->manuallyRemoved;
    }

    /**
     * @return bool
     */
    public function getManuallyAdded()
    {
        return $this->manuallyAdded;
    }

    /**
     * @param bool $manuallyAdded
     */
    public function setManuallyAdded($manuallyAdded): void
    {
        $this->manuallyAdded = $manuallyAdded;
    }

    /**
     * @return bool
     */
    public function wasManuallyAdded()
    {
        return $this->manuallyAdded;
    }
}
