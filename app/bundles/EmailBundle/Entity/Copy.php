<?php

namespace Mautic\EmailBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: CopyRepository::class)]
#[ORM\Table(name: 'email_copies')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Copy
{
    /**
     * MD5 hash of the content.
     *
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 32)]
    private $id;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'date_created', type: 'datetime')]
    private $dateCreated;

    /**
     * @var string|null
     */
    private $body;

    private ?string $bodyText = null;

    /**
     * @var string|null
     */
    private $subject;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addNullableField('body', 'text');
        $builder->addNullableField('bodyText', 'text', 'body_text');

        $builder->addNullableField('subject', 'text');
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
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
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @param \DateTime $dateCreated
     */
    public function setDateCreated($dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject($subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBodyText(): ?string
    {
        return $this->bodyText;
    }

    public function setBodyText(?string $bodyText): self
    {
        $this->bodyText = $bodyText;

        return $this;
    }
}
