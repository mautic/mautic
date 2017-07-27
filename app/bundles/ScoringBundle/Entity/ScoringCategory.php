<?php

/*
 * @author      Captivea (QCH)
 */

namespace Mautic\ScoringBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class ScoringCategory.
 */
class ScoringCategory extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $published;
    
    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var int
     */
    private $orderIndex;

    /**
     * @var bool
     */
    private $updateGlobalScore = false;

    /**
     * @var float
     */
    private $globalScoreModifier;

    public function __clone() {
        $this->id        = null;
        $this->published = false;
        
        parent::__clone();
    }

    public function __construct() {
        
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata) {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('scoring_categories')
            ->setCustomRepositoryClass('Mautic\ScoringBundle\Entity\ScoringCategoryRepository');

        $builder->addIdColumns();
        
        $builder->addPublishDates();
        
        $builder->createField('orderIndex', 'integer')
            ->columnName('order_index')
            ->build();
        
        $builder->createField('updateGlobalScore', 'boolean')
            ->columnName('update_global_score')
            ->build();
        
        $builder->createField('globalScoreModifier', 'float')
            ->columnName('global_score_modifier')
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata) {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );
    }
    
    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * @return float
     */
    public function getGlobalScoreModifier() {
        return $this->globalScoreModifier;
    }
    
    /**
     * @return int
     */
    public function getOrderIndex() {
        return $this->orderIndex;
    }
    
    /**
     * @return bool
     */
    public function getPublished() {
        return $this->published;
    }
    
    /**
     * @return bool
     */
    public function getUpdateGlobalScore() {
        return $this->updateGlobalScore;
    }
    
    /**
     * 
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    
    /**
     * 
     * @param string $description
     * @return $this
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }
    
    /**
     * 
     * @param float $globalScoreModifier
     * @return $this
     */
    public function setGlobalScoreModifier($globalScoreModifier) {
        $this->globalScoreModifier = floatval($globalScoreModifier);
        return $this;
    }
    
    /**
     * 
     * @param int $orderIndex
     * @return $this
     */
    public function setOrderIndex($orderIndex) {
        $this->orderIndex = intval($orderIndex);
        return $this;
    }
    
    /**
     * 
     * @param bool $published
     * @return $this
     */
    public function setPublished($published) {
        $this->published = !!$published;
        return $this;
    }
    
    /**
     * 
     * @param bool $updateGlobalScore
     * @return $this
     */
    public function setUpdateGlobalScore($updateGlobalScore) {
        $this->updateGlobalScore = !!$updateGlobalScore;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Set publishUp.
     *
     * @param \DateTime $publishUp
     *
     * @return $this
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * Get publishUp.
     *
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * Set publishDown.
     *
     * @param \DateTime $publishDown
     *
     * @return $this
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * Get publishDown.
     *
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }
}
