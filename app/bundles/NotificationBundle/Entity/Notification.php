<?php

namespace Mautic\NotificationBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('notification:notifications:viewown')"),
        new Post(security: "is_granted('notification:notifications:create')"),
        new Get(security: "is_granted('notification:notifications:viewown')"),
        new Put(security: "is_granted('notification:notifications:editown')"),
        new Patch(security: "is_granted('notification:notifications:editother')"),
        new Delete(security: "is_granted('notification:notifications:deleteown')"),
    ],
    normalizationContext: [
        'groups'                  => ['notification:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category'],
    ],
    denormalizationContext: [
        'groups'                  => ['notification:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
class Notification extends FormEntity implements UuidInterface, TranslationEntityInterface
{
    use UuidTrait;
    use TranslationEntityTrait;

    /**
     * @var int
     */
    #[Groups(['notification:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $description;

    /**
     * @var string|null
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $url;

    /**
     * @var string
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $heading;

    /**
     * @var string
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $message;

    /**
     * @var string|null
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $button;

    /**
     * @var array
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $utmTags = [];

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['notification:read'])]
    private $readCount = 0;

    /**
     * @var int
     */
    #[Groups(['notification:read'])]
    private $sentCount = 0;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category|null
     **/
    #[Groups(['notification:read', 'notification:write'])]
    private $category;

    /**
     * @var ArrayCollection<int, LeadList>
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $lists;

    /**
     * @var ArrayCollection<int, Stat>
     */
    private $stats;

    /**
     * @var string|null
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $notificationType = 'template';

    /**
     * @var bool
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $mobile = false;

    /**
     * @var ?array
     */
    #[Groups(['notification:read', 'notification:write'])]
    private $mobileSettings;

    public function __clone()
    {
        $this->id        = null;
        $this->stats     = new ArrayCollection();
        $this->sentCount = 0;
        $this->readCount = 0;

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists               = new ArrayCollection();
        $this->stats               = new ArrayCollection();
        $this->translationChildren = new ArrayCollection();
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

        $builder->setTable('push_notifications')
            ->setCustomRepositoryClass(NotificationRepository::class);

        $builder->addIdColumns();

        $builder->createField('url', 'text')
            ->nullable()
            ->build();

        $builder->createField('heading', 'text')
            ->build();

        $builder->createField('message', 'text')
            ->build();

        $builder->createField('button', 'text')
            ->nullable()
            ->build();

        $builder->createField('utmTags', 'array')
            ->columnName('utm_tags')
            ->nullable()
            ->build();

        $builder->createField('notificationType', 'text')
            ->columnName('notification_type')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->createField('readCount', 'integer')
            ->columnName('read_count')
            ->build();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->addCategory();

        $builder->createManyToMany('lists', LeadList::class)
            ->setJoinTable('push_notification_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('notification_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('notification')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('mobile', 'boolean')->build();

        $builder->createField('mobileSettings', 'array')->build();

        static::addUuidField($builder);

        self::addTranslationMetadata($builder, self::class);
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

        $metadata->addPropertyConstraint(
            'heading',
            new NotBlank(
                [
                    'message' => 'mautic.core.heading.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'message',
            new NotBlank(
                [
                    'message' => 'mautic.core.message.required',
                ]
            )
        );

        $metadata->addConstraint(new Callback(
            function (Notification $notification, ExecutionContextInterface $context): void {
                $type = $notification->getNotificationType();
                if ('list' == $type) {
                    $validator  = $context->getValidator();
                    $violations = $validator->validate(
                        $notification->getLists(),
                        [
                            new LeadListAccess(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                            new NotBlank(
                                [
                                    'message' => 'mautic.lead.lists.required',
                                ]
                            ),
                        ]
                    );

                    if (count($violations) > 0) {
                        $string = (string) $violations;
                        $context->buildViolation($string)
                            ->atPath('lists')
                            ->addViolation();
                    }
                }
            },
        ));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('notification')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'heading',
                    'message',
                    'url',
                    'language',
                    'category',
                    'button',
                ]
            )
            ->addProperties(
                [
                    'utmTags',
                    'publishUp',
                    'publishDown',
                    'readCount',
                    'sentCount',
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
     * @return mixed
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
     * @return mixed
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
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param string $heading
     */
    public function setHeading($heading): void
    {
        $this->isChanged('heading', $heading);
        $this->heading = $heading;
    }

    /**
     * @return string
     */
    public function getButton()
    {
        return $this->button;
    }

    public function setButton($button): void
    {
        $this->isChanged('button', $button);
        $this->button = $button;
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
     * @return array
     */
    public function getUtmTags()
    {
        return $this->utmTags;
    }

    /**
     * @param array $utmTags
     */
    public function setUtmTags($utmTags)
    {
        $this->isChanged('utmTags', $utmTags);
        $this->utmTags = $utmTags;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url): void
    {
        $this->isChanged('url', $url);
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getReadCount()
    {
        return $this->readCount;
    }

    /**
     * @return $this
     */
    public function setReadCount($readCount)
    {
        $this->readCount = $readCount;

        return $this;
    }

    /**
     * @return mixed
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
     * @return mixed
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

    public function getSentCount(bool $includeVariants = false): mixed
    {
        return ($includeVariants) ? $this->getAccumulativeTranslationCount('getSentCount') : $this->sentCount;
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
     * @return mixed
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @return Notification
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
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return string
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * @param string $notificationType
     */
    public function setNotificationType($notificationType): void
    {
        $this->isChanged('notificationType', $notificationType);
        $this->notificationType = $notificationType;
    }

    /**
     * @return bool
     */
    public function isMobile()
    {
        return $this->mobile;
    }

    /**
     * @param bool $mobile
     *
     * @return $this
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * @return array
     */
    public function getMobileSettings()
    {
        return $this->mobileSettings ?? [];
    }

    /**
     * @return $this
     */
    public function setMobileSettings(array $mobileSettings)
    {
        $this->mobileSettings = $mobileSettings;

        return $this;
    }
}
