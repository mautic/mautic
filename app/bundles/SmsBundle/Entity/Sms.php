<?php

namespace Mautic\SmsBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Mautic\CoreBundle\Validator\EntityEvent;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Mautic\ProjectBundle\Entity\ProjectTrait;
use Mautic\SmsBundle\Form\Validator\Constraints\MediaMaxAllowedSize;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('sms:smses:viewown')"),
        new Post(security: "is_granted('sms:smses:create')"),
        new Get(security: "is_granted('sms:smses:viewown', object)"),
        new Put(security: "is_granted('sms:smses:editown', object)"),
        new Patch(security: "is_granted('sms:smses:editother', object)"),
        new Delete(security: "is_granted('sms:smses:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['sms:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['category', 'lists'],
    ],
    denormalizationContext: [
        'groups'                  => ['sms:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
/**
 * @use TranslationEntityTrait<Sms>
 * @use VariantEntityTrait<Sms>
 */
class Sms extends FormEntity implements UuidInterface, TranslationEntityInterface, VariantEntityInterface
{
    use UuidTrait;
    use ProjectTrait;
    use TranslationEntityTrait;
    use VariantEntityTrait;

    public const TABLE_NAME = 'sms_messages';

    /**
     * @var int
     */
    #[Groups(['sms:read'])]
    private $id;

    /**
     * @var string
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $description;

    /**
     * @var string
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $message;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $publishDown;

    /**
     * @var int
     */
    #[Groups(['sms:read'])]
    private $sentCount = 0;

    /**
     * @var Category|null
     **/
    #[Groups(['sms:read', 'sms:write'])]
    private $category;

    /**
     * @var ArrayCollection<int, LeadList>
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $lists;

    /**
     * @var ArrayCollection<int, Stat>
     */
    private $stats;

    /**
     * @var string|null
     */
    #[Groups(['sms:read', 'sms:write'])]
    private $smsType = 'template';

    /**
     * @var array<mixed>
     */
    #[Groups(['sms:read', 'sms:write'])]
    private array $media = [];

    #[Groups(['sms:read', 'sms:write'])]
    private bool $isMms = false;

    #[Groups(['sms:read'])]
    private int $pendingCount = 0;

    public function __clone()
    {
        $this->id        = null;
        $this->stats     = new ArrayCollection();
        $this->sentCount = 0;

        $this->clearTranslations();

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->stats = new ArrayCollection();
        $this->initializeProjects();
        $this->translationChildren = new ArrayCollection();
    }

    public function clearStats(): void
    {
        $this->stats = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable(self::TABLE_NAME)
            ->setCustomRepositoryClass(SmsRepository::class);

        $builder->addIdColumns();

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

        $builder->addCategory();

        $builder->createField('media', Types::JSON)
            ->columnName('media')
            ->build();

        $builder->createField('isMms', Types::BOOLEAN)
            ->columnName('is_mms')
            ->option('default', 0)
            ->build();

        $builder->createManyToMany('lists', LeadList::class)
            ->setJoinTable('sms_message_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('sms_id', 'id', true, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('sms')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        self::addTranslationMetadata($builder, self::class);

        static::addUuidField($builder);
        self::addProjectsField($builder, 'sms_projects_xref', 'sms_id');
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(message: 'mautic.core.name.required')
        );

        $metadata->addPropertyConstraint(
            'media',
            new Count(max: 10, maxMessage: 'mautic.sms.form.max.media.error')
        );

        $metadata->addConstraint(new Callback(
            function (Sms $sms, ExecutionContextInterface $context): void {
                $type      = $sms->getSmsType();
                $validator = $context->getValidator();
                if ('list' == $type) {
                    $violations = $validator->validate(
                        $sms->getLists(),
                        [
                            new NotBlank(message: 'mautic.lead.lists.required'),
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
        ));

        $metadata->addConstraint(new EntityEvent());
        $metadata->addConstraint(new MediaMaxAllowedSize());
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
                    'lists',
                ]
            )
            ->build();

        self::addProjectsInLoadApiMetadata($metadata, 'sms');
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
     * @return string|null
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
    public function setName($name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
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
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Category|null
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return $this
     */
    public function setCategory($category): static
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return string|null
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
     * @return \DateTimeInterface|null
     */
    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return $this
     */
    public function setPublishDown($publishDown): static
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @return $this
     */
    public function setPublishUp($publishUp): static
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
    public function setSentCount($sentCount): static
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return ArrayCollection|LeadList[]
     */
    public function getLists()
    {
        return $this->lists;
    }

    public function addList(LeadList $list): static
    {
        $this->lists[] = $list;

        return $this;
    }

    public function removeList(LeadList $list): void
    {
        $this->lists->removeElement($list);
    }

    /**
     * @return ArrayCollection<int, Stat>
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return string|null
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

    public function setPendingCount(int $pendingCount): static
    {
        $this->pendingCount = $pendingCount;

        return $this;
    }

    public function getPendingCount(): int
    {
        return $this->pendingCount;
    }

    /**
     * @param array<mixed> $media
     */
    public function setMedia(array $media): self
    {
        $this->media = $media;

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function getMedia(): array
    {
        return $this->media;
    }

    public function setIsMms(bool $isMms): self
    {
        $this->isMms = $isMms;

        return $this;
    }

    public function getIsMms(): bool
    {
        return (bool) $this->isMms;
    }
}
