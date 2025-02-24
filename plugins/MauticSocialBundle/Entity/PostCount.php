<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PostCount
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var Monitoring|null
     */
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

        $builder->setTable('monitor_post_count')
            ->setCustomRepositoryClass(PostCountRepository::class);

        $builder->addId();

        $builder->createManyToOne('monitor', 'Monitoring')
            ->addJoinColumn('monitor_id', 'id', true, false, 'CASCADE')
            ->build();

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
     *
     * @return $this
     */
    public function setMonitor($monitor)
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
     *
     * @return $this
     */
    public function setPostCount($postCount)
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

    /**
     * @return $this
     */
    public function setPostDate($postDate)
    {
        $this->postDate = $postDate;

        return $this;
    }
}
