<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FiltersEntityTrait;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class DynamicContent extends FormEntity implements VariantEntityInterface, TranslationEntityInterface
{
    use TranslationEntityTrait;
    use VariantEntityTrait;
    use FiltersEntityTrait;

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
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var \DateTime
     */
    private $publishUp;

    /**
     * @var \DateTime
     */
    private $publishDown;

    /**
     * @var string
     */
    private $content;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * @var bool
     */
    private $isCampaignBased = true;

    /**
     * @var string
     */
    private $slotName;

    /**
     * DynamicContent constructor.
     */
    public function __construct()
    {
        $this->stats           = new ArrayCollection();
        $this->variantChildren = new ArrayCollection();
    }

    /**
     * Clone method.
     */
    public function __clone()
    {
        $this->id              = null;
        $this->sentCount       = 0;
        $this->stats           = new ArrayCollection();
        $this->variantChildren = new ArrayCollection();

        parent::__clone();
    }

    /**
     * Clear stats.
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('dynamic_content')
            ->addIndex(['is_campaign_based'], 'is_campaign_based_index')
            ->addIndex(['slot_name'], 'slot_name_index')
            ->setCustomRepositoryClass('Mautic\DynamicContentBundle\Entity\DynamicContentRepository')
            ->addLifecycleEvent('cleanSlotName', Events::prePersist)
            ->addLifecycleEvent('cleanSlotName', Events::preUpdate);

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addPublishDates();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->createField('content', 'text')
            ->columnName('content')
            ->nullable()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('dynamicContent')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
        self::addFiltersMetadata($builder);

        $builder->createField('isCampaignBased', 'boolean')
                ->columnName('is_campaign_based')
                ->option('default', 1)
                ->build();

        $builder->createField('slotName', 'string')
                ->columnName('slot_name')
                ->nullable()
                ->build();
    }

    /**
     * @param ClassMetadata $metadata
     *
     * @throws \Symfony\Component\Validator\Exception\ConstraintDefinitionException
     * @throws \Symfony\Component\Validator\Exception\InvalidOptionsException
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public static function loadValidatorMetaData(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(['message' => 'mautic.core.name.required']));

        $metadata->addConstraint(new Callback([
            'callback' => function (self $dwc, ExecutionContextInterface $context) {
                if (!$dwc->getIsCampaignBased() && '' === $dwc->getSlotName()) {
                    $validator = $context->getValidator();
                    $violations = $validator->validate(
                        $dwc->getSlotName(),
                        [
                            new NotBlank(
                                [
                                    'message' => 'mautic.core.value.required',
                                ]
                            ),
                        ]
                    );
                    if (count($violations) > 0) {
                        foreach ($violations as $violation) {
                            $context->buildViolation($violation->getMessage())
                                    ->atPath('slotName')
                                    ->addViolation();
                        }
                    }
                }
            },
        ]));
    }

    /**
     * @param ApiMetadataDriver $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('dwc')
            ->addListProperties([
                'id',
                'name',
                'category',
            ])
            ->addProperties([
                'publishUp',
                'publishDown',
                'sentCount',
                'variantParent',
                'variantChildren',
                'content',
                'filters',
                'isCampaignBased',
                'slotName',
            ])
            ->setMaxDepth(1, 'variantParent')
            ->setMaxDepth(1, 'variantChildren')
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'variantParent' || $prop == 'translationParent' || $prop == 'category') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = [$currentId, $newId];
            }
        } else {
            parent::isChanged($prop, $val);
        }
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return $this
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \Mautic\CategoryBundle\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param \Mautic\CategoryBundle\Entity\Category $category
     *
     * @return $this
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
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
     * @return \DateTime
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
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
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @param bool $includeVariants
     *
     * @return mixed
     */
    public function getSentCount($includeVariants = false)
    {
        return $includeVariants ? $this->getAccumulativeTranslationCount('getSentCount') : $this->sentCount;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return bool
     */
    public function getIsCampaignBased()
    {
        return $this->isCampaignBased;
    }

    /**
     * @param bool $isCampaignBased
     *
     * @return $this
     */
    public function setIsCampaignBased($isCampaignBased)
    {
        $this->isChanged('isCampaignBased', $isCampaignBased);
        $this->isCampaignBased = $isCampaignBased;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlotName()
    {
        return $this->slotName;
    }

    /**
     * @param string $slotName
     *
     * @return $this
     */
    public function setSlotName($slotName)
    {
        $this->isChanged('slotName', $slotName);
        $this->slotName = $slotName;

        return $this;
    }

    /**
     * Lifecycle callback to clear the slot name if is_campaign is true.
     */
    public function cleanSlotName()
    {
        if ($this->getIsCampaignBased()) {
            $this->setSlotName('');
        }
    }
}
