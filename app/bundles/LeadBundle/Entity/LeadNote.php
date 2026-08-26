<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;

class LeadNote extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Lead
     */
    private $lead;

    /**
     * @var string
     */
    #[NotBlank(message: 'mautic.lead.note.text.notblank')]
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
            ->setCustomRepositoryClass(LeadNoteRepository::class);

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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $text
     */
    public function setText($text): static
    {
        $this->isChanged('text', $text);
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $type
     */
    public function setType($type): static
    {
        $this->isChanged('type', $type);
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Form validation rules.
     */
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
     * @return \DateTimeInterface|null
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
