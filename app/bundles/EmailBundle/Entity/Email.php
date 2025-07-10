<?php

namespace Mautic\EmailBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\DynamicContentEntityTrait;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\TranslationEntityInterface;
use Mautic\CoreBundle\Entity\TranslationEntityTrait;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Mautic\CoreBundle\Entity\VariantEntityInterface;
use Mautic\CoreBundle\Entity\VariantEntityTrait;
use Mautic\CoreBundle\Helper\UrlHelper;
use Mautic\CoreBundle\Validator\EntityEvent;
use Mautic\EmailBundle\Validator\EmailLists;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenList;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\PageBundle\Entity\Page;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "email:read"
 *        },
 *       "swagger_definition_name"="Read",
 *       "api_included"={"category", "asset", "page", "translationChildren", "unsubscribeForm", "fields", "actions", "lists", "preferenceCenter", "assetAttachments"}
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "email:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Email extends FormEntity implements VariantEntityInterface, TranslationEntityInterface, UuidInterface
{
    use VariantEntityTrait;
    use TranslationEntityTrait;
    use DynamicContentEntityTrait;
    use UuidTrait;

    public const MAX_NAME_SUBJECT_LENGTH = 190;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $id;

    /**
     * @var string
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $name;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $description;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $subject;

    /**
     * @var bool|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $useOwnerAsMailer;

    private ?string $preheaderText = null;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $fromAddress;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $fromName;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $replyToAddress;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $bccAddress;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $template;

    /**
     * @var array
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $content = [];

    /**
     * @var array
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $utmTags = [];

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $plainText;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $customHtml;

    /**
     * @var string|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $emailType = 'template';

    /**
     * @var \DateTimeInterface|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $publishUp;

    /**
     * @var \DateTimeInterface|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $publishDown;

    /**
     * @var bool|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $publicPreview = false;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $readCount = 0;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $sentCount = 0;

    /**
     * @var int
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $revision = 1;

    /**
     * @var Category|null
     *
     * @Groups({"email:read", "email:write"})
     **/
    private $category;

    /**
     * @var ArrayCollection<LeadList>
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $lists;

    /**
     * @var ArrayCollection<LeadList>
     */
    private $excludedLists;

    /**
     * @var ArrayCollection<Stat>
     */
    private $stats;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $variantSentCount = 0;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $variantReadCount = 0;

    /**
     * @var Form|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $unsubscribeForm;

    /**
     * @var Page|null
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $preferenceCenter;

    /**
     * @var ArrayCollection<Asset>
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $assetAttachments;

    /**
     * Used to identify the page for the builder.
     */
    private $sessionId;

    /**
     * @var array
     *
     * @Groups({"email:read", "email:write", "download:read"})
     */
    private $headers = [];

    /**
     * @var int
     */
    private $pendingCount = 0;

    /**
     * @var int
     *
     * @Groups({"email:read", "download:read"})
     */
    private $queuedCount = 0;

    private ?EmailDraft $draft = null;

    private bool $isCloned = false;

    /**
     * In some use cases, we need to get the original email ID after it's been cloned.
     *
     * @var int
     */
    private $clonedId;

    public function __clone()
    {
        $this->isCloned          = true;
        $this->clonedId          = $this->id;
        $this->id                = null;
        $this->sentCount         = 0;
        $this->readCount         = 0;
        $this->revision          = 0;
        $this->variantSentCount  = 0;
        $this->variantReadCount  = 0;
        $this->variantStartDate  = null;
        $this->emailType         = null;
        $this->sessionId         = 'new_'.hash('sha1', uniqid(mt_rand()));
        $this->plainText         = null;
        $this->publishUp         = null;
        $this->publishDown       = null;
        $this->clearTranslations();
        $this->clearVariants();
        $this->clearStats();
        $this->setDraft(null);

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists               = new ArrayCollection();
        $this->excludedLists       = new ArrayCollection();
        $this->stats               = new ArrayCollection();
        $this->translationChildren = new ArrayCollection();
        $this->variantChildren     = new ArrayCollection();
        $this->assetAttachments    = new ArrayCollection();
        $this->setDateAdded(new \DateTime());
        $this->setDateModified(new \DateTime());
    }

    public function clearStats(): void
    {
        $this->stats = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('emails')
            ->setCustomRepositoryClass(EmailRepository::class)
            ->addLifecycleEvent('cleanUrlsInContent', Events::preUpdate)
            ->addLifecycleEvent('cleanUrlsInContent', Events::prePersist);

        $builder->addIdColumns();
        $builder->addNullableField('subject', Types::TEXT);
        $builder->addNullableField('preheaderText', Types::STRING, 'preheader_text');
        $builder->addNullableField('fromAddress', Types::STRING, 'from_address');
        $builder->addNullableField('fromName', Types::STRING, 'from_name');
        $builder->addNullableField('replyToAddress', Types::STRING, 'reply_to_address');
        $builder->addNullableField('bccAddress', Types::STRING, 'bcc_address');
        $builder->addNullableField('useOwnerAsMailer', Types::BOOLEAN, 'use_owner_as_mailer');
        $builder->addNullableField('template', Types::STRING);
        $builder->addNullableField('content', Types::ARRAY);
        $builder->addNullableField('utmTags', Types::ARRAY, 'utm_tags');
        $builder->addNullableField('plainText', Types::TEXT, 'plain_text');
        $builder->addNullableField('customHtml', Types::TEXT, 'custom_html');
        $builder->addNullableField('emailType', Types::TEXT, 'email_type');
        $builder->addPublishDates();
        $builder->addNamedField('readCount', Types::INTEGER, 'read_count');
        $builder->addNamedField('sentCount', Types::INTEGER, 'sent_count');
        $builder->addNamedField('variantSentCount', Types::INTEGER, 'variant_sent_count');
        $builder->addNamedField('variantReadCount', Types::INTEGER, 'variant_read_count');
        $builder->addField('revision', Types::INTEGER);
        $builder->addCategory();

        $builder->createManyToMany('lists', LeadList::class)
            ->setJoinTable('email_list_xref')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('email_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createManyToMany('excludedLists', LeadList::class)
            ->setJoinTable('email_list_excluded')
            ->setIndexBy('id')
            ->addInverseJoinColumn('leadlist_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('email_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->createOneToMany('stats', 'Stat')
            ->setIndexBy('id')
            ->mappedBy('email')
            ->cascadePersist()
            ->fetchExtraLazy()
            ->build();

        self::addTranslationMetadata($builder, self::class);
        self::addVariantMetadata($builder, self::class);
        self::addDynamicContentMetadata($builder);

        $builder->createManyToOne('unsubscribeForm', Form::class)
            ->addJoinColumn('unsubscribeform_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToOne('preferenceCenter', Page::class)
            ->addJoinColumn('preference_center_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToMany('assetAttachments', Asset::class)
            ->setJoinTable('email_assets_xref')
            ->addInverseJoinColumn('asset_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('email_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();

        $builder->addField('headers', Types::JSON);

        $builder->addNullableField('publicPreview', Types::BOOLEAN, 'public_preview');

        $builder->createOneToOne('draft', EmailDraft::class)
            ->mappedBy('email')
            ->fetchExtraLazy()
            ->cascadeAll()
            ->build();

        static::addUuidField($builder);
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
            'name',
            new Length(
                [
                    'max'        => self::MAX_NAME_SUBJECT_LENGTH,
                    'maxMessage' => 'mautic.email.name.length',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'subject',
            new NotBlank(
                [
                    'message' => 'mautic.core.subject.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'subject',
            new Length(
                [
                    'max'        => self::MAX_NAME_SUBJECT_LENGTH,
                    'maxMessage' => 'mautic.email.subject.length',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'preheaderText',
            new Length(
                [
                    'max'        => 130,
                    'maxMessage' => 'mautic.email.preheader_text.length',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'fromAddress',
            new EmailOrEmailTokenList(),
        );

        $metadata->addPropertyConstraint(
            'replyToAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                [
                    'message' => 'mautic.core.email.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'bccAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                [
                    'message' => 'mautic.core.email.required',
                ]
            )
        );

        $metadata->addConstraint(new EmailLists());
        $metadata->addConstraint(new EntityEvent());

        $metadata->addConstraint(new Callback([
            'callback' => function (Email $email, ExecutionContextInterface $context): void {
                if ($email->isVariant()) {
                    // Get a summation of weights
                    $parent   = $email->getVariantParent();
                    $children = $parent ? $parent->getVariantChildren() : $email->getVariantChildren();

                    $total = 0;
                    foreach ($children as $child) {
                        $settings = $child->getVariantSettings();
                        $total += (int) $settings['weight'];
                    }

                    if ($total > 100) {
                        $context->buildViolation('mautic.core.variant_weights_invalid')
                            ->atPath('variantSettings[weight]')
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
        $metadata->setGroupPrefix('email')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'subject',
                    'language',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'fromAddress',
                    'fromName',
                    'replyToAddress',
                    'bccAddress',
                    'useOwnerAsMailer',
                    'utmTags',
                    'preheaderText',
                    'customHtml',
                    'plainText',
                    'template',
                    'emailType',
                    'publishUp',
                    'publishDown',
                    'publicPreview',
                    'readCount',
                    'sentCount',
                    'revision',
                    'assetAttachments',
                    'variantStartDate',
                    'variantSentCount',
                    'variantReadCount',
                    'variantParent',
                    'variantChildren',
                    'translationParent',
                    'translationChildren',
                    'unsubscribeForm',
                    'dynamicContent',
                    'lists',
                    'headers',
                ]
            )
            ->build();
    }

    protected function isChanged($prop, $val)
    {
        $getter  = 'get'.ucfirst($prop);
        $current = $this->$getter();

        if ('variantParent' == $prop || 'translationParent' == $prop || 'category' == $prop || 'list' == $prop) {
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
     * @param ?string $name
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
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param ?string $description
     *
     * @return Email
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    public function setId(int $id): Email
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return ?Category
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
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return $this
     */
    public function setContent($content)
    {
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
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
     * @return mixed
     */
    public function getReadCount($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getReadCount') : $this->readCount;
    }

    /**
     * @return $this
     */
    public function setReadCount($readCount)
    {
        $this->readCount = $readCount;

        return $this;
    }

    public function getIsClone(): bool
    {
        return $this->isCloned;
    }

    /**
     * @return mixed
     */
    public function getRevision()
    {
        return $this->revision;
    }

    /**
     * @return $this
     */
    public function setRevision($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @return $this
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->isChanged('subject', $subject);
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return ?bool
     */
    public function getUseOwnerAsMailer()
    {
        return $this->useOwnerAsMailer;
    }

    /**
     * @param bool $useOwnerAsMailer
     *
     * @return $this
     */
    public function setUseOwnerAsMailer($useOwnerAsMailer)
    {
        $this->useOwnerAsMailer = $useOwnerAsMailer;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @param mixed $fromAddress
     *
     * @return Email
     */
    public function setFromAddress($fromAddress)
    {
        $this->isChanged('fromAddress', $fromAddress);
        $this->fromAddress = $fromAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @param mixed $fromName
     *
     * @return Email
     */
    public function setFromName($fromName)
    {
        $this->isChanged('fromName', $fromName);
        $this->fromName = $fromName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReplyToAddress()
    {
        return $this->replyToAddress;
    }

    /**
     * @param mixed $replyToAddress
     *
     * @return Email
     */
    public function setReplyToAddress($replyToAddress)
    {
        $this->isChanged('replyToAddress', $replyToAddress);
        $this->replyToAddress = $replyToAddress;

        return $this;
    }

    public function getPreheaderText(): ?string
    {
        return $this->preheaderText;
    }

    public function setPreheaderText(?string $preheaderText): Email
    {
        $this->isChanged('preheaderText', $preheaderText);
        $this->preheaderText = $preheaderText;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBccAddress()
    {
        return $this->bccAddress;
    }

    /**
     * @param mixed $bccAddress
     *
     * @return Email
     */
    public function setBccAddress($bccAddress)
    {
        $this->isChanged('bccAddress', $bccAddress);
        $this->bccAddress = $bccAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return $this
     */
    public function setTemplate($template)
    {
        $this->isChanged('template', $template);
        $this->template = $template;

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

    /**
     * @param bool $includeVariants
     *
     * @return mixed
     */
    public function getSentCount($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getSentCount') : $this->sentCount;
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
    public function getVariantSentCount($includeVariants = false)
    {
        return ($includeVariants) ? $this->getAccumulativeVariantCount('getVariantSentCount') : $this->variantSentCount;
    }

    /**
     * @return $this
     */
    public function setVariantSentCount($variantSentCount)
    {
        $this->variantSentCount = $variantSentCount;

        return $this;
    }

    /**
     * @return ArrayCollection<int, LeadList>
     */
    public function getLists()
    {
        return $this->lists;
    }

    /**
     * Add list.
     *
     * @return Email
     */
    public function addList(LeadList $list)
    {
        $this->listsChangedAdd('lists', $list->getId());
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Set the lists for this translation.
     */
    public function setLists(array $lists = [])
    {
        $lists = new ArrayCollection($lists);
        $this->listsChangedSet('lists', $this->getListKeys($lists));
        $this->lists = $lists;

        return $this;
    }

    /**
     * Remove list.
     */
    public function removeList(LeadList $list): void
    {
        $this->listsChangedRemove('lists', $list->getId());
        $this->lists->removeElement($list);
    }

    /**
     * @return Collection<int, LeadList>
     */
    public function getExcludedLists(): Collection
    {
        return $this->excludedLists;
    }

    public function addExcludedList(LeadList $excludedList): void
    {
        $this->listsChangedAdd('excludedLists', $excludedList->getId());
        $this->excludedLists->add($excludedList);
    }

    public function removeExcludedList(LeadList $excludedList): void
    {
        $this->listsChangedRemove('excludedLists', $excludedList->getId());
        $this->excludedLists->removeElement($excludedList);
    }

    /**
     * @return mixed
     */
    public function getPlainText()
    {
        return $this->plainText;
    }

    /**
     * @return $this
     */
    public function setPlainText($plainText)
    {
        $this->plainText = $plainText;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariantReadCount()
    {
        return $this->variantReadCount;
    }

    /**
     * @return $this
     */
    public function setVariantReadCount($variantReadCount)
    {
        $this->variantReadCount = $variantReadCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return mixed
     */
    public function getCustomHtml()
    {
        return $this->customHtml;
    }

    /**
     * @return $this
     */
    public function setCustomHtml($customHtml)
    {
        $this->customHtml = $customHtml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnsubscribeForm()
    {
        return $this->unsubscribeForm;
    }

    /**
     * @return $this
     */
    public function setUnsubscribeForm(Form $unsubscribeForm = null)
    {
        $this->unsubscribeForm = $unsubscribeForm;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPreferenceCenter()
    {
        return $this->preferenceCenter;
    }

    /**
     * @return $this
     */
    public function setPreferenceCenter(Page $preferenceCenter = null)
    {
        $this->preferenceCenter = $preferenceCenter;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEmailType()
    {
        return $this->emailType;
    }

    /**
     * @param mixed $emailType
     *
     * @return Email
     */
    public function setEmailType($emailType)
    {
        $this->emailType = $emailType;

        return $this;
    }

    /**
     * Add asset.
     *
     * @return Email
     */
    public function addAssetAttachment(Asset $asset)
    {
        $this->assetAttachments[] = $asset;

        return $this;
    }

    /**
     * Remove asset.
     */
    public function removeAssetAttachment(Asset $asset): void
    {
        $this->assetAttachments->removeElement($asset);
    }

    /**
     * Get assetAttachments.
     *
     * @return Collection
     */
    public function getAssetAttachments()
    {
        return $this->assetAttachments;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     *
     * @return Email
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Lifecycle callback to clean URLs in the content.
     */
    public function cleanUrlsInContent(): void
    {
        if (is_string($this->plainText)) {
            $this->decodeAmpersands($this->plainText);
        }

        if (is_string($this->customHtml)) {
            $this->decodeAmpersands($this->customHtml);
        }
    }

    /**
     * Check all links in content and decode ampersands.
     */
    private function decodeAmpersands(string &$content): void
    {
        if (preg_match_all('/((https?|ftps?):\/\/)([a-zA-Z0-9-\.{}]*[a-zA-Z0-9=}]*)(\??)([^\s\"\]]+)?/i', $content, $matches)) {
            foreach ($matches[0] as $url) {
                $content = str_replace($url, UrlHelper::decodeAmpersands($url), $content);
            }
        }
    }

    /**
     * Calculate Read Percentage for each Email.
     */
    public function getReadPercentage($includevariants = false): float|int
    {
        if ($this->getSentCount($includevariants) > 0) {
            return round($this->getReadCount($includevariants) / $this->getSentCount($includevariants) * 100, 2);
        } else {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function getPublicPreview()
    {
        return $this->publicPreview;
    }

    /**
     * @return bool
     */
    public function isPublicPreview()
    {
        return $this->publicPreview;
    }

    /**
     * @param bool $publicPreview
     *
     * @return $this
     */
    public function setPublicPreview($publicPreview)
    {
        $this->isChanged('publicPreview', $publicPreview);
        $this->publicPreview = $publicPreview;

        return $this;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setQueuedCount($count)
    {
        $this->queuedCount = $count;

        return $this;
    }

    /**
     * @return int
     */
    public function getQueuedCount()
    {
        return $this->queuedCount;
    }

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setPendingCount($count)
    {
        $this->pendingCount = $count;

        return $this;
    }

    /**
     * @return int
     */
    public function getPendingCount()
    {
        return $this->pendingCount;
    }

    public function getClonedId(): ?int
    {
        return $this->clonedId;
    }

    public function isBackgroundSending(): bool
    {
        return $this->isPublished() && !empty($this->getPublishUp()) && ($this->getPublishUp() < new \DateTime());
    }

    private function listsChangedAdd(string $property, ?int $id): void
    {
        $this->initListChanges($property);
        $this->changes[$property][1] = array_unique(array_merge($this->changes[$property][1], [$id]));
    }

    private function listsChangedRemove(string $property, ?int $id): void
    {
        $this->initListChanges($property);
        $this->changes[$property][1] = array_diff($this->changes[$property][1], [$id]);
    }

    public function getDraft(): ?EmailDraft
    {
        return $this->draft;
    }

    public function setDraft(?EmailDraft $draft): void
    {
        $this->draft = $draft;
    }

    public function hasDraft(): bool
    {
        return null !== $this->getDraft();
    }

    public function getDraftContent(): ?string
    {
        return $this->getDraft()?->getHtml();
    }

    /**
     * @param mixed[] $ids
     */
    private function listsChangedSet(string $property, array $ids): void
    {
        $this->initListChanges($property);
        $this->changes[$property][1] = $ids;
    }

    private function initListChanges(string $property): void
    {
        if (!isset($this->changes[$property])) {
            $list                     = $this->$property;
            $current                  = $this->getListKeys($list);
            $this->changes[$property] = [$current, $current];
        }
    }

    /**
     * @param iterable<mixed> $list
     *
     * @return mixed[]
     */
    private function getListKeys(iterable $list): array
    {
        $keys = [];

        foreach ($list as $key => $value) {
            $keys[] = $key;
        }

        return $keys;
    }
}
