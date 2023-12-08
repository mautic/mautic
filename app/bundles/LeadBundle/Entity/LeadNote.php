<?php

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class LeadNote extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var \Mautic\LeadBundle\Entity\Lead
     */
    private $lead;

    /**
     * @var string
     */
    private $text;

    /**
     * @var string|null
     */
    private $type = 'general';

    /**
     * @var \DateTimeInterface
     */
    private $dateTime;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('lead_notes')
            ->setCustomRepositoryClass(\Mautic\LeadBundle\Entity\LeadNoteRepository::class);

        $builder->addId();

        $builder->addLead(false, 'CASCADE', false, 'notes');

        $builder->addField('text', 'text');

        $builder->createField('type', 'string')
            ->length(50)
            ->nullable()
            ->build();

        $builder->createField('dateTime', 'datetime')
            ->columnName('date_time')
            ->nullable()
            ->build();
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('leadNote')
            ->addProperties(
                [
                    'id',
                    'text',
                    'type',
                    'dateTime',
                    'lead',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set text.
     *
     * @param string $text
     *
     * @return LeadNote
     */
    public function setText($text)
    {
        $this->isChanged('text', $text);
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return LeadNote
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Form validation rules.
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('text', new NotBlank(
            ['message' => 'mautic.lead.note.text.notblank']
        ));
    }

    /**
     * @return Lead
     */
    public function getLead()
    {
        return $this->lead;
    }

    public function setLead(Lead $lead): void
    {
        $this->lead = $lead;
    }

    public function convertToArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param mixed $dateTime
     */
    public function setDateTime($dateTime): void
    {
        $this->dateTime = $dateTime;
    }
}
