<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PipedrivePipeline
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $pipelineId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $active;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_crm_pipedrive_pipelines');
        $builder->addId();
        $builder->addNamedField('pipelineId', 'integer', 'pipeline_id', false);
        $builder->addNamedField('name', 'string', 'name', false);
        $builder->addNamedField('active', 'boolean', 'active', false);
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getPipelineId()
    {
        return $this->pipelineId;
    }

    /**
     * @param int $pipelineId
     *
     * @return PipedrivePipeline
     */
    public function setPipelineId($pipelineId)
    {
        $this->pipelineId = $pipelineId;

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
     * @return PipedrivePipeline
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return PipedrivePipeline
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }
}
