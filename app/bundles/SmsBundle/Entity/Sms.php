<?php

namespace Mautic\SmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
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

class Sms extends FormEntity
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
     * @var string|null
     */
    private $description;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var string
     */
    private $message;

    /**
     * @var \DateTimeInterface
     */
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    private $publishDown;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     **/
    private $category;

    /**
     * @var ArrayCollection<int, \Mautic\LeadBundle\Entity\LeadList>
     */
    private $lists;

    /**
     * @var ArrayCollection<int, \Mautic\SmsBundle\Entity\Stat>
     */
    private $stats;

    /**
     * @var string|null
     */
    private $smsType = 'template';

    /**
     * @var int
     */
    private $pendingCount = 0;

    /**
     * @var int
     */
    private $deliveredCount = 0;

    /**
     * @var int
     */
    private $readCount = 0;

    /**
     * @var int
     */
    private $failedCount = 0;

    /**
     * @var array<int|string|array<int|string>>
     */
    private $properties = [];

    public function __clone()
    {
        $this->id             = null;
        $this->stats          = new ArrayCollection();
        $this->sentCount      = 0;
        $this->readCount      = 0;
        $this->deliveredCount = 0;
        $this->readCount      = 0;
        $this->failedCount    = 0;

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->stats = new ArrayCollection();
    }

    /**
     * Clear stats.
     */
    public function clearStats(): void
    {
        $this->stats = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('sms_messages')
            ->setCustomRepositoryClass(SmsRepository::class);

        $builder->addIdColumns();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->createField('message', 'text')
            ->build();

        $builder->createField('smsType', 'text')
            ->columnName('sms_type')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->createField('deliveredCount', Types::INTEGER)
            ->columnName('delivered_count')
            ->build();

        $builder->createField('readCount', Types::INTEGER)
            ->columnName('read_count')
            ->build();

        $builder->createField('failedCount', Types::INTEGER)
            ->columnName('failed_count')
            ->build();

        $builder->addField('properties', Types::JSON);

        $builder->addCategory();

        $builder->createManyToMany('lists', LeadList::class)
            ->setJoinTable('sms_message_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('sms_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('sms')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addConstraint(new Callback([
            'callback' => function (Sms $sms, ExecutionContextInterface $context): void {
                $type = $sms->getSmsType();
                if ('list' == $type) {
                    $validator  = $context->getValidator();
                    $violations = $validator->validate(
                        $sms->getLists(),
                        [
                            new NotBlank(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                            new LeadListAccess(),
                        ]
                    );

                    foreach ($violations as $violation) {
                        $context->buildViolation($violation->getMessage())
                            ->atPath('lists')
                            ->addViolation();
                    }
                }
            },
        ]));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('sms')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'message',
                    'language',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'publishUp',
                    'publishDown',
                    'sentCount',
                    'deliveredCount',
                    'readCount',
                    'failedCount',
                    'properties',
                ]
            )
            ->build();
    }

    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ('category' == $prop || 'list' == $prop) {
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
     */
    public function setDescription($description): void
    {
        $this->isChanged('description', $description);
        $this->description = $description;
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
     * @return \Mautic\CategoryBundle\Entity\Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return $this
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message): void
    {
        $this->isChanged('message', $message);
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return $this
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @return $this
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;

        return $this;
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
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
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @return Sms
     */
    public function addList(LeadList $list)
    {
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Remove list.
     */
    public function removeList(LeadList $list): void
    {
        $this->lists->removeElement($list);
    }

    /**
     * @return ArrayCollection
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return string
     */
    public function getSmsType()
    {
        return $this->smsType;
    }

    /**
     * @param string $smsType
     */
    public function setSmsType($smsType): void
    {
        $this->isChanged('smsType', $smsType);
        $this->smsType = $smsType;
    }

    /**
     * @param int $pendingCount
     *
     * @return Sms
     */
    public function setPendingCount($pendingCount)
    {
        $this->pendingCount = $pendingCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getPendingCount()
    {
        return $this->pendingCount;
    }

    /**
     * @return int
     */
    public function getDeliveredCount()
    {
        return $this->deliveredCount;
    }

    public function getDeliveredRatio(): float
    {
        if (!$this->deliveredCount) {
            return 0.0;
        }

        return round($this->deliveredCount / $this->sentCount * 100, 2);
    }

    /**
     * @param int $deliveredCount
     *
     * @return Sms
     */
    public function setDeliveredCount($deliveredCount)
    {
        $this->isChanged('deliveredCount', $deliveredCount);
        $this->deliveredCount = $deliveredCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getReadCount()
    {
        return $this->readCount;
    }

    public function getReadRatio(): float
    {
        if (!$this->readCount) {
            return 0.0;
        }

        return round($this->readCount / $this->sentCount * 100, 2);
    }

    /**
     * @param int $readCount
     *
     * @return Sms
     */
    public function setReadCount($readCount)
    {
        $this->isChanged('readCount', $readCount);
        $this->readCount = $readCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }

    public function getFailedRatio(): float
    {
        if (!$this->failedCount) {
            return 0.0;
        }

        return round($this->failedCount / $this->sentCount * 100, 2);
    }

    /**
     * @param int $failedCount
     *
     * @return Sms
     */
    public function setFailedCount($failedCount)
    {
        $this->isChanged('failedCount', $failedCount);
        $this->failedCount = $failedCount;

        return $this;
    }

    /**
     * @return array<int|string|array<int|string>>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @param array<int|string|array<int|string>> $properties
     */
    public function setProperties(array $properties): static
    {
        $this->isChanged('properties', $properties);
        $this->properties = $properties;

        return $this;
    }
}
