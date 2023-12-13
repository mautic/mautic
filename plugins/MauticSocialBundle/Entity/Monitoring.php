<?php

namespace MauticPlugin\MauticSocialBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Monitoring extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     */
    private $category;

    /**
     * @var array
     */
    private $lists = [];

    /**
     * @var string|null
     */
    private $networkType;

    /**
     * @var int
     */
    private $revision = 1;

    /**
     * @var array
     */
    private $stats = [];

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @var \DateTimeInterface
     */
    private $publishDown;

    /**
     * @var \DateTimeInterface
     */
    private $publishUp;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('monitoring')
            ->setCustomRepositoryClass(\MauticPlugin\MauticSocialBundle\Entity\MonitoringRepository::class)
            ->addLifecycleEvent('cleanMonitorData', 'preUpdate')
            ->addLifecycleEvent('cleanMonitorData', 'prePersist');

        $builder->addCategory();

        $builder->addIdColumns('title');

        $builder->addNullableField('lists', 'array');

        $builder->addNamedField('networkType', 'string', 'network_type', true);

        $builder->addField('revision', 'integer');

        $builder->addNullableField('stats', 'array');

        $builder->addNullableField('properties', 'array');

        $builder->addPublishDates();
    }

    /**
     * Constraints for required fields.
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('title', new Assert\NotBlank(
            ['message' => 'mautic.core.title.required']
        ));

        $metadata->addPropertyConstraint('networkType', new Assert\NotBlank(
            ['message' => 'mautic.social.network.type']
        ));
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     * Get lists.
     *
     * @return array
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Get network type.
     *
     * @return string
     */
    public function getNetworkType()
    {
        return $this->networkType;
    }

    /**
     * Get revision.
     *
     * @return int
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * Get statistics.
     *
     * @return array
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get properties.
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set the category id.
     *
     * @param \Mautic\CategoryBundle\Entity\Category|null $category
     */
    public function setCategory($category): void
    {
        $this->isChanged('category', $category);
        $this->category = $category;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Monitoring
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Set the monitor lists.
     *
     * @return Monitoring
     */
    public function setLists($lists)
    {
        $this->isChanged('lists', $lists);
        $this->lists = $lists;

        return $this;
    }

    /**
     * Set the network type.
     *
     * @return Monitoring
     */
    public function setNetworkType($networkType)
    {
        $this->isChanged('networkType', $networkType);
        $this->networkType = $networkType;

        return $this;
    }

    /**
     * Set the revision counter.
     *
     * @param int $revision
     *
     * @return Monitoring
     */
    public function setRevision($revision)
    {
        $this->isChanged('revision', $revision);
        $this->revision = $revision;

        return $this;
    }

    /**
     * Set the statistics.
     *
     * @param array $stats
     *
     * @return Monitoring
     */
    public function setStats($stats)
    {
        $this->isChanged('stats', $stats);
        $this->stats = $stats;

        return $this;
    }

    /**
     * Set name.
     *
     * @param string $title
     *
     * @return Monitoring
     */
    public function setTitle($title)
    {
        $this->isChanged('title', $title);
        $this->title = $title;

        return $this;
    }

    /**
     * Set properties.
     *
     * @param array $properties
     *
     * @return Monitoring
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTime $publishDown
     *
     * @return Monitoring
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return Monitoring
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Clear out old properties data.
     */
    public function cleanMonitorData(): void
    {
        $property = $this->getProperties();

        if (!array_key_exists('checknames', $property)) {
            $property['checknames'] = 0;
        }

        // clean up property array for the twitter handle
        if ('twitter_handle' == $this->getNetworkType()) {
            $this->setProperties(
                [
                    'handle'     => $property['handle'],
                    'checknames' => $property['checknames'],
                ]
            );
        }

        // clean up property array for the hashtag
        if ('twitter_hashtag' == $this->getNetworkType()) {
            $this->setProperties(
                [
                    'hashtag'    => $property['hashtag'],
                    'checknames' => $property['checknames'],
                ]
            );
        }

        // clean up clean up property array for the custom action
        if ('twitter_custom' == $this->getNetworkType()) {
            $this->setProperties(
                [
                    'custom' => $property['custom'],
                ]
            );
        }

        // if the property is not new and the old property doesn't match the new one
        if (!$this->isNew() && $property != $this->getProperties()) {
            // reset stats on save of edited
            $this->setStats([]);
        }
    }
}
