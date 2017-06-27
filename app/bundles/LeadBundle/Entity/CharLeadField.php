<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class CharLeadField.
 */
class CharLeadField
{
    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var LeadField
     */
    private $leadField;

    /**
     * @var string
     */
    private $value;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('char_lead_fields_leads_xref');

        $builder->createManyToOne('lead', 'Mautic\LeadBundle\Entity\Lead')
                ->cascadePersist()
                ->cascadeMerge()
                ->addJoinColumn('lead_id', 'id')
                ->makePrimaryKey()
                ->build();

        $builder->createManyToOne('leadField', 'Mautic\LeadBundle\Entity\LeadField')
                ->cascadePersist()
                ->cascadeMerge()
                ->addJoinColumn('lead_field_id', 'id')
                ->makePrimaryKey()
                ->build();

        $builder->createField('value', 'string')
                ->columnName('value')
                ->build();
    }

    /**
     * @return Lead
     */
    public function getLead() {
        return $this->lead;
    }

    /**
     * @param Lead
     *
     * @return CharLeadField
     */
    public function setLead($lead) {
        $this->lead = $lead;
        return $this;
    }

    /**
     * @return LeadField
     */
    public function getLeadField() {
        return $this->leadField;
    }

    /**
     * @param LeadField
     *
     * @return CharLeadField
     */
    public function setLeadField($leadField) {
        $this->leadField = $leadField;
        return $this;
    }

    /**
     * @return Value
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param Value
     *
     * @return CharLeadField
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

}
