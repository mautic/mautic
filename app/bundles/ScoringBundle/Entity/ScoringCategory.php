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
    private $orderIndex = 1;

    /**
     * @var bool
     */
    private $updateGlobalScore = false;

    /**
     * @var float
     */
    private $globalScoreModifier = 0.00;
    
    /**
     * @var boolean
     */
    private $isGlobalScore = false;
    
    /**
     *
     * @var array
     */
    private $leadValues;
    
    /**
     *
     * @var array 
     */
    private $companyValues;
    
    /**
     *
     * @var array 
     */
    private $usedByPoints;
    
    /**
     *
     * @var array 
     */
    private $usedByTriggers;
    
    /**
     *
     * @var array 
     */
    private $usedByEvents;

    public function __clone() {
        $this->id        = null;
        $this->published = false;
        
        parent::__clone();
    }

    public function __construct() {
        $this->leadValues = new ArrayCollection();
        $this->companyValues = new ArrayCollection();
        $this->usedByEvents = new ArrayCollection();
        $this->usedByPoints = new ArrayCollection();
        $this->usedByTriggers = new ArrayCollection();
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
        
        $builder->createField('isGlobalScore', 'boolean')
            ->columnName('is_global_score')
            ->build();
        
        $builder->createOneToMany('leadValues', 'Mautic\ScoringBundle\Entity\ScoringValue')
                ->mappedBy('scoringCategory')
                ->cascadeAll()
                ->fetchLazy()
                ->build();
        
        $builder->createOneToMany('companyValues', 'Mautic\ScoringBundle\Entity\ScoringCompanyValue')
                ->mappedBy('scoringCategory')
                ->cascadeAll()
                ->fetchLazy()
                ->build();
        
        $builder->createOneToMany('usedByPoints', 'Mautic\PointBundle\Entity\Point')
                ->mappedBy('scoringCategory')
                ->cascadeAll()
                ->fetchLazy()
                ->build();
        
        $builder->createOneToMany('usedByTriggers', 'Mautic\PointBundle\Entity\Trigger')
                ->mappedBy('scoringCategory')
                ->cascadeAll()
                ->fetchLazy()
                ->build();
        
        $builder->createOneToMany('usedByEvents', 'Mautic\CampaignBundle\Entity\Event')
                ->mappedBy('scoringCategory')
                ->cascadeAll()
                ->fetchLazy()
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
    
    /**
     * @return bool
     */
    public function getIsGlobalScore() {
        return $this->isGlobalScore;
    }
    
    /**
     * 
     * @param bool $isGlobalScore
     * @return $this
     */
    public function setIsGlobalScore($isGlobalScore) {
        $this->isGlobalScore = !!$isGlobalScore;
        return $this;
    }
    
    /**
     * @param \Mautic\ScoringBundle\Entity\ScoringValue $leadValue
     *
     * @return $this
     */
    public function addLeadValue(\Mautic\ScoringBundle\Entity\ScoringValue $leadValue)
    {
        $this->leadValues[] = $leadValue;

        return $this;
    }

    /**
     * @param \Mautic\ScoringBundle\Entity\ScoringValue $leadValue
     */
    public function removeLeadValue(\Mautic\ScoringBundle\Entity\ScoringValue $leadValue)
    {
        $this->leadValues->removeElement($leadValue);
    }

    /**
     * @param ArrayCollection $leadValues
     * @return $this
     */
    public function setLeadValues(ArrayCollection $leadValues)
    {
        $this->leadValues = $leadValues;
        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getLeadValues()
    {
        return $this->leadValues;
    }
    
        
    /**
     * @param \Mautic\ScoringBundle\Entity\ScoringCompanyValue $companyValue
     *
     * @return $this
     */
    public function addCompanyValue(\Mautic\ScoringBundle\Entity\ScoringCompanyValue $companyValue)
    {
        $this->companyValues[] = $companyValue;

        return $this;
    }

    /**
     * @param \Mautic\ScoringBundle\Entity\ScoringCompanyValue $companyValue
     */
    public function removeCompanyValue(\Mautic\ScoringBundle\Entity\ScoringCompanyValue $companyValue)
    {
        $this->companyValues->removeElement($companyValue);
    }

    /**
     * @param ArrayCollection $leadValues
     * @return $this
     */
    public function setCompanyValues(ArrayCollection $companyValues)
    {
        $this->companyValues = $companyValues;
        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getCompanyValues()
    {
        return $this->companyValues;
    }
    
    /**
     * @param \Mautic\CampaignBundle\Entity\Event $event
     *
     * @return $this
     */
    public function addUsedByEvent(\Mautic\CampaignBundle\Entity\Event $event)
    {
        $this->usedByEvents[] = $event;

        return $this;
    }

    /**
     * @param \Mautic\CampaignBundle\Entity\Event $event
     */
    public function removeUsedByEvent(\Mautic\CampaignBundle\Entity\Event $event)
    {
        $this->usedByEvents->removeElement($event);
    }

    /**
     * @param ArrayCollection $events
     * @return $this
     */
    public function setUsedByEvents(ArrayCollection $events)
    {
        $this->usedByEvents = $events;
        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getUsedByEvents()
    {
        return $this->usedByEvents;
    }
    
    /**
     * @param \Mautic\PointBundle\Entity\Point $point
     *
     * @return $this
     */
    public function addUsedByPoint(\Mautic\PointBundle\Entity\Point $point)
    {
        $this->usedByPoints[] = $point;

        return $this;
    }

    /**
     * @param \Mautic\PointBundle\Entity\Point $point
     */
    public function removeUsedByPoint(\Mautic\PointBundle\Entity\Point $point)
    {
        $this->usedByPoints->removeElement($point);
    }

    /**
     * @param ArrayCollection $points
     * @return $this
     */
    public function setUsedByPoints(ArrayCollection $points)
    {
        $this->usedByPoints = $points;
        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getUsedByPoints()
    {
        return $this->usedByPoints;
    }
    
    /**
     * @param \Mautic\PointBundle\Entity\Trigger $trigger
     *
     * @return $this
     */
    public function addUsedByTrigger(\Mautic\PointBundle\Entity\Trigger $trigger)
    {
        $this->usedByTriggers[] = $trigger;

        return $this;
    }

    /**
     * @param \Mautic\PointBundle\Entity\Trigger $trigger
     */
    public function removeUsedByTrigger(\Mautic\PointBundle\Entity\Trigger $trigger)
    {
        $this->usedByTriggers->removeElement($trigger);
    }

    /**
     * @param ArrayCollection $triggers
     * @return $this
     */
    public function setUsedByTriggers(ArrayCollection $triggers)
    {
        $this->usedByTriggers = $triggers;
        return $this;
    }
    
    /**
     * @return ArrayCollection
     */
    public function getUsedByTriggers()
    {
        return $this->usedByTriggers;
    }
    
    /**
     * @return boolean
     */
    public function isUsedAnywhere() {
        return !$this->getUsedByEvents()->isEmpty()
            || !$this->getUsedByPoints()->isEmpty()
            || !$this->getUsedByTriggers()->isEmpty();
    }
}
