<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostCountRepository::class)]
#[ORM\Table(name: 'monitor_post_count')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class PostCount
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
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
    #[ORM\Column(name: 'post_date', type: 'date')]
    private $postDate;

    /**
     * @var int
     */
    #[ORM\Column(name: 'post_count', type: 'integer')]
    private $postCount;

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
