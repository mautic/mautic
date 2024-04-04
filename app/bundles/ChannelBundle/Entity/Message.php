<?php

namespace Mautic\ChannelBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidationClassMetadata;

class Message extends FormEntity
{
    /**
     * @var ?int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var ?string
     */
    private $description;

    /**
     * @var ?\DateTimeInterface
     */
    private $publishUp;

    /**
     * @var ?\DateTimeInterface
     */
    private $publishDown;

    /**
     * @var ?Category
     */
    private $category;

    /**
     * @var ArrayCollection<int,Channel>
     */
    private $channels;

    public function __clone()
    {
        $this->id = null;
    }

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('messages')
                ->setCustomRepositoryClass(MessageRepository::class)
                ->addIndex(['date_added'], 'date_message_added');

        $builder
            ->addIdColumns()
            ->addPublishDates()
            ->addCategory();

        $builder->createOneToMany('channels', Channel::class)
            ->setIndexBy('channel')
            ->orphanRemoval()
            ->mappedBy('message')
            ->cascadeMerge()
            ->cascadePersist()
            ->cascadeDetach()
            ->build();
    }

    public static function loadValidatorMetadata(ValidationClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new NotBlank([
            'message' => 'mautic.core.name.required',
        ]));
    }

    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('message')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'channels',
                    'category',
                ]
            )
            ->build();
    }

    public function __construct()
    {
        $this->channels = new ArrayCollection();
    }

    /**
     * @return ?int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ?string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ?string $name
     *
     * @return Message
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param ?string $description
     *
     * @return Message
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @param ?\DateTime $publishUp
     *
     * @return Message
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @param ?\DateTime $publishDown
     *
     * @return Message
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return ?Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param ?Category $category
     *
     * @return Message
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return ArrayCollection<int,Channel>
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * @param ArrayCollection<int,Channel> $channels
     *
     * @return Message
     */
    public function setChannels($channels)
    {
        $this->isChanged('channels', $channels);
        $this->channels = $channels;

        return $this;
    }

    public function addChannel(Channel $channel): void
    {
        if (!$this->channels->contains($channel)) {
            $channel->setMessage($this);
            $this->isChanged('channels', $channel);

            $this->channels[$channel->getChannel()] = $channel;
        }
    }

    public function removeChannel(Channel $channel): void
    {
        if ($channel->getId()) {
            $this->isChanged('channels', $channel->getId());
        }
        $this->channels->removeElement($channel);
    }
}
