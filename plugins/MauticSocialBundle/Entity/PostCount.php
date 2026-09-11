<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: PostCountRepository::class)]
#[ORM\Table(name: 'monitor_post_count')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PostCount
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Monitoring|null
     */
    #[ORM\ManyToOne(targetEntity: Monitoring::class)]
    #[ORM\JoinColumn(name: 'monitor_id', onDelete: 'CASCADE')]
    private $monitor;

    /**
     * @var \DateTimeInterface
     */
    private $postDate;

    /**
     * @var int
     */
    private $postCount;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addId();

        $builder->addNamedField('postDate', 'date', 'post_date');

        $builder->addNamedField('postCount', 'integer', 'post_count');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Monitoring
     */
    public function getMonitor()
    {
        return $this->monitor;
    }

    /**
     * @param Monitoring $monitor
     */
    public function setMonitor($monitor): static
    {
        $this->monitor = $monitor;

        return $this;
    }

    /**
     * @return int
     */
    public function getPostCount()
    {
        return $this->postCount;
    }

    /**
     * @param int $postCount
     */
    public function setPostCount($postCount): static
    {
        $this->postCount = $postCount;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    public function setPostDate($postDate): static
    {
        $this->postDate = $postDate;

        return $this;
    }
}
