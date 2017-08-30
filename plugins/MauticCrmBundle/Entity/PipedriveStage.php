<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PipedriveStage
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $stageId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $active = true;

    /**
     * @var int
     */
    private $order;

    /**
     * @var PipedrivePipeline
     */
    private $pipeline;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('plugin_crm_pipedrive_stages')
            ->addUniqueConstraint(['stage_id'], 'unique_stage')
            ->addUniqueConstraint(['stage_id', 'pipeline_id'], 'unique_pipeline_stage');

        $builder->addId();
        $builder->addNamedField('stageId', 'integer', 'stage_id', false);
        $builder->addNamedField('name', 'string', 'name', false);
        $builder->addNamedField('active', 'boolean', 'active', false);
        $builder->addNamedField('order', 'integer', 'order_nr', false);

        $pipeline = $builder->createManyToOne('pipeline', 'MauticPlugin\MauticCrmBundle\Entity\PipedrivePipeline');
        $pipeline->addJoinColumn('pipeline_id', 'id', $nullable = false, $unique = false, $onDelete = 'CASCADE')
            ->build();
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
    public function getStageId()
    {
        return $this->stageId;
    }

    /**
     * @param int $stageId
     *
     * @return PipedriveStage
     */
    public function setStageId($stageId)
    {
        $this->stageId = $stageId;

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
     * @return PipedriveStage
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
     * @return PipedriveStage
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param int $order
     *
     * @return PipedriveStage
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * @return PipedrivePipeline
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * @param PipedrivePipeline $pipeline
     *
     * @return PipedriveStage
     */
    public function setPipeline($pipeline)
    {
        $this->pipeline = $pipeline;

        return $this;
    }
}
