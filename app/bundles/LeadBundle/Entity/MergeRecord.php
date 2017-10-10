<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class MergeRecord
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead
     */
    private $contact;

    /**
     * @var \DateTime
     */
    private $dateAdded;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $mergedId;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('contact_merge_records')
            ->setCustomRepositoryClass('Mautic\LeadBundle\Entity\MergeRecordRepository')
            ->addIndex(['date_added'], 'contact_merge_date_added')
            ->addIndex(['merged_id'], 'contact_merge_ids');

        $builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        $builder->addContact()
            ->addDateAdded()
            ->addNamedField('mergedId', 'integer', 'merged_id')
            ->addField('name', 'string');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Lead
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * @param Lead $contact
     *
     * @return MergeRecord
     */
    public function setContact(Lead $contact)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime $dateAdded
     *
     * @return MergeRecord
     */
    public function setDateAdded(\DateTime $dateAdded = null)
    {
        if (null === $dateAdded) {
            $dateAdded = new \DateTime();
        }

        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return MergeRecord
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getMergedId()
    {
        return $this->mergedId;
    }

    /**
     * @param int $mergedId
     *
     * @return MergeRecord
     */
    public function setMergedId($mergedId)
    {
        $this->mergedId = (int) $mergedId;

        return $this;
    }
}
