<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Helper\EmojiHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Email
 *
 * @package Mautic\EmailBundle\Entity
 */
class Email extends FormEntity
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
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $fromAddress;

    /**
     * @var string
     */
    private $fromName;

    /**
     * @var string
     */
    private $replyToAddress;

    /**
     * @var string
     */
    private $bccAddress;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $language = 'en';

    /**
     * @var array
     */
    private $content = array();

    /**
     * @var string
     */
    private $plainText;

    /**
     * @var string
     */
    private $customHtml;

    /**
     * @var
     */
    private $emailType;

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
    private $readCount = 0;

    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var int
     */
    private $revision = 1;

    /**
     * @var \Mautic\CategoryBundle\Entity\Category
     **/
    private $category;

    /**
     * @var ArrayCollection
     */
    private $lists;

    /**
     * @var ArrayCollection
     */
    private $stats;

    /**
     * @var Email
     **/
    private $variantParent = null;

    /**
     * @var ArrayCollection
     **/
    private $variantChildren;

    /**
     * @var array
     */
    private $variantSettings = array();

    /**
     * @var \DateTime
     */
    private $variantStartDate;

    /**
     * @var int
     */
    private $variantSentCount = 0;

    /**
     * @var int
     */
    private $variantReadCount = 0;

    /**
     * @var \Mautic\FormBundle\Entity\Form
     */
    private $unsubscribeForm;

    /**
     * @var ArrayCollection
     */
    private $assetAttachments;

    /**
     * Used to identify the page for the builder
     *
     * @var
     */
    private $sessionId;

    public function __clone()
    {
        $this->id               = null;
        $this->stats            = new ArrayCollection();
        $this->sentCount        = 0;
        $this->readCount        = 0;
        $this->revision         = 0;
        $this->variantSentCount = 0;
        $this->variantStartDate = null;
        $this->emailType        = null;
        $this->sessionId        = 'new_' . hash('sha1', uniqid(mt_rand()));

        parent::__clone();
    }

    public function __construct ()
    {
        $this->lists            = new ArrayCollection();
        $this->stats            = new ArrayCollection();
        $this->variantChildren  = new ArrayCollection();
        $this->assetAttachments = new ArrayCollection();
    }

    /**
     * Clear stats
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    /**
     * Clear variants
     */
    public function clearVariants()
    {
        $this->variantChildren = new ArrayCollection();
        $this->variantParent   = null;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('emails')
            ->setCustomRepositoryClass('Mautic\EmailBundle\Entity\EmailRepository');

        $builder->addIdColumns();

        $builder->createField('subject', 'text')
            ->nullable()
            ->build();

        $builder->createField('fromAddress', 'string')
            ->columnName('from_address')
            ->nullable()
            ->build();

        $builder->createField('fromName', 'string')
            ->columnName('from_name')
            ->nullable()
            ->build();

        $builder->createField('replyToAddress', 'string')
            ->columnName('reply_to_address')
            ->nullable()
            ->build();

        $builder->createField('bccAddress', 'string')
            ->columnName('bcc_address')
            ->nullable()
            ->build();

        $builder->createField('template', 'string')
            ->nullable()
            ->build();

        $builder->createField('language', 'string')
            ->columnName('lang')
            ->build();

        $builder->createField('content', 'array')
            ->nullable()
            ->build();

        $builder->createField('plainText', 'text')
            ->columnName('plain_text')
            ->nullable()
            ->build();

        $builder->createField('customHtml', 'text')
            ->columnName('custom_html')
            ->nullable()
            ->build();

        $builder->createField('emailType', 'text')
            ->columnName('email_type')
            ->nullable()
            ->build();

        $builder->addPublishDates();

        $builder->createField('readCount', 'integer')
            ->columnName('read_count')
            ->build();

        $builder->createField('sentCount', 'integer')
            ->columnName('sent_count')
            ->build();

        $builder->addField('revision', 'integer');

        $builder->addCategory();

        $builder->createManyToMany('lists', 'Mautic\LeadBundle\Entity\LeadList')
            ->setJoinTable('email_list_xref')
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

        $builder->createManyToOne('variantParent', 'Email')
            ->inversedBy('variantChildren')
            ->addJoinColumn('variant_parent_id', 'id')
            ->build();

        $builder->createOneToMany('variantChildren', 'Email')
            ->setIndexBy('id')
            ->mappedBy('variantParent')
            ->fetchLazy()
            ->build();

        $builder->createField('variantSettings', 'array')
            ->columnName('variant_settings')
            ->nullable()
            ->build();

        $builder->createField('variantStartDate', 'datetime')
            ->columnName('variant_start_date')
            ->nullable()
            ->build();

        $builder->createField('variantSentCount', 'integer')
            ->columnName('variant_sent_count')
            ->build();

        $builder->createField('variantReadCount', 'integer')
            ->columnName('variant_read_count')
            ->build();

        $builder->createManyToOne('unsubscribeForm', 'Mautic\FormBundle\Entity\Form')
            ->addJoinColumn('unsubscribeform_id', 'id', true, false, 'SET NULL')
            ->build();

        $builder->createManyToMany('assetAttachments', 'Mautic\AssetBundle\Entity\Asset')
            ->setJoinTable('email_assets_xref')
            ->addInverseJoinColumn('asset_id', 'id', false, false, 'CASCADE')
            ->addJoinColumn('email_id', 'id', false, false, 'CASCADE')
            ->fetchExtraLazy()
            ->build();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata (ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                array(
                    'message' => 'mautic.core.name.required'
                )
            )
        );

        $metadata->addPropertyConstraint(
            'fromAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required'
                )
            )
        );

        $metadata->addPropertyConstraint(
            'replyToAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required'
                )
            )
        );

        $metadata->addPropertyConstraint(
            'bccAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required'
                )
            )
        );

        $metadata->addConstraint(new Callback(array(
            'callback' => function (Email $email, ExecutionContextInterface $context) {
                $type = $email->getEmailType();
                if ($type == 'list') {
                    $validator  = $context->getValidator();
                    $violations = $validator->validate(
                        $email->getLists(),
                        array(
                            new LeadListAccess(
                                array(
                                    'message' => 'mautic.lead.lists.required'
                                )
                            ),
                            new NotBlank(
                                array(
                                    'message' => 'mautic.lead.lists.required'
                                )
                            )
                        )
                    );

                    if (count($violations) > 0) {
                        $string = (string) $violations;
                        $context->buildViolation($string)
                            ->atPath('lists')
                            ->addViolation();
                    }
                }

                if ($email->isVariant()) {
                    // Get a summation of weights
                    $parent = $email->getVariantParent();
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
            }
        )));
    }

    /**
     * Prepares the metadata for API usage
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
    {
        $metadata->setGroupPrefix('email')
            ->addListProperties(
                array(
                    'id',
                    'name',
                    'subject',
                    'language',
                    'category',

                )
            )
            ->addProperties(
                array(
                    'fromAddress',
                    'fromName',
                    'replyToAddress',
                    'bccAddress',
                    'publishUp',
                    'publishDown',
                    'readCount',
                    'sentCount',
                    'revision',
                    'assetAttachments',
                    'variantStartDate',
                    'variantSentCount',
                    'variantReadCount',
                    'variantParent',
                    'variantChildren'
                )
            )
            ->build();
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged ($prop, $val)
    {
        $getter  = "get" . ucfirst($prop);
        $current = $this->$getter();

        if ($prop == 'variantParent' || $prop == 'category' || $prop == 'list') {
            $currentId = ($current) ? $current->getId() : '';
            $newId     = ($val) ? $val->getId() : null;
            if ($currentId != $newId) {
                $this->changes[$prop] = array($currentId, $newId);
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
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
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
     * @param mixed $description
     *
     * @return Email
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getCategory ()
    {
        return $this->category;
    }

    /**
     * @param $category
     *
     * @return $this
     */
    public function setCategory ($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;

        return $this;
    }

    /**
     * @return array
     */
    public function getContent ()
    {
        return $this->content;
    }

    /**
     * @param $content
     *
     * @return $this
     */
    public function setContent ($content)
    {
        // Ensure safe emoji
        $content = EmojiHelper::toShort($content);

        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReadCount ($includeVariants = false)
    {
        $count = $this->readCount;

        if ($includeVariants && $this->isVariant()) {
            $parent = $this->getVariantParent();
            if ($parent) {
                $count   += $parent->getReadCount();
                $children = $parent->getVariantChildren();
            } else {
                $children = $this->getVariantChildren();
            }
            foreach ($children as $child) {
                if ($child->getId() !== $this->id) {
                    $count += $child->getReadCount();
                }
            }
        }

        return $count;
    }

    /**
     * @param $readCount
     *
     * @return $this
     */
    public function setReadCount ($readCount)
    {
        $this->readCount = $readCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLanguage ()
    {
        return $this->language;
    }

    /**
     * @param $language
     *
     * @return $this
     */
    public function setLanguage ($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRevision ()
    {
        return $this->revision;
    }

    /**
     * @param $revision
     *
     * @return $this
     */
    public function setRevision ($revision)
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessionId ()
    {
        return $this->sessionId;
    }

    /**
     * @param $sessionId
     *
     * @return $this
     */
    public function setSessionId ($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSubject ()
    {
        return $this->subject;
    }

    /**
     * @param $subject
     *
     * @return $this
     */
    public function setSubject ($subject)
    {
        $this->isChanged('subject', $subject);
        $this->subject = $subject;

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
        $this->replyToAddress = $replyToAddress;

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
        $this->bccAddress = $bccAddress;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTemplate ()
    {
        return $this->template;
    }

    /**
     * @param $template
     *
     * @return $this
     */
    public function setTemplate ($template)
    {
        $this->isChanged('template', $template);
        $this->template = $template;

        return $this;
    }

    /**
     * Add variantChildren
     *
     * @param \Mautic\EmailBundle\Entity\Email $variantChildren
     *
     * @return Email
     */
    public function addVariantChild (Email $variantChildren)
    {
        if (!$this->variantChildren->contains($variantChildren)) {
            $this->variantChildren[] = $variantChildren;
        }

        return $this;
    }

    /**
     * Remove variantChildren
     *
     * @param \Mautic\EmailBundle\Entity\Email $variantChildren
     */
    public function removeVariantChild (Email $variantChildren)
    {
        $this->variantChildren->removeElement($variantChildren);
    }

    /**
     * Get variantChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariantChildren ()
    {
        return $this->variantChildren;
    }

    /**
     * Set variantParent
     *
     * @param \Mautic\EmailBundle\Entity\Email $variantParent
     *
     * @return Email
     */
    public function setVariantParent (Email $variantParent = null)
    {
        $this->isChanged('variantParent', $variantParent);
        $this->variantParent = $variantParent;

        return $this;
    }

    /**
     * Get variantParent
     *
     * @return \Mautic\EmailBundle\Entity\Email
     */
    public function getVariantParent ()
    {
        return $this->variantParent;
    }

    /**
     * Set variantSettings
     *
     * @param array $variantSettings
     *
     * @return Email
     */
    public function setVariantSettings ($variantSettings)
    {
        $this->isChanged('variantSettings', $variantSettings);
        $this->variantSettings = $variantSettings;

        return $this;
    }

    /**
     * Get variantSettings
     *
     * @return array
     */
    public function getVariantSettings ()
    {
        return $this->variantSettings;
    }

    /**
     * @return mixed
     */
    public function getVariantStartDate ()
    {
        return $this->variantStartDate;
    }

    /**
     * @param $variantStartDate
     *
     * @return $this
     */
    public function setVariantStartDate ($variantStartDate)
    {
        $this->isChanged('variantStartDate', $variantStartDate);
        $this->variantStartDate = $variantStartDate;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishDown ()
    {
        return $this->publishDown;
    }

    /**
     * @param $publishDown
     *
     * @return $this
     */
    public function setPublishDown ($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPublishUp ()
    {
        return $this->publishUp;
    }

    /**
     * @param $publishUp
     *
     * @return $this
     */
    public function setPublishUp ($publishUp)
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
    public function getSentCount ($includeVariants = false)
    {
        $count = $this->sentCount;

        if ($includeVariants && $this->isVariant()) {
            $parent = $this->getVariantParent();
            if ($parent) {
                $count   += $parent->getSentCount();
                $children = $parent->getVariantChildren();
            } else {
                $children = $this->getVariantChildren();
            }
            foreach ($children as $child) {
                if ($child->getId() !== $this->id) {
                    $count += $child->getSentCount();
                }
            }
        }

        return $count;
    }

    /**
     * @param $sentCount
     *
     * @return $this
     */
    public function setSentCount ($sentCount)
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariantSentCount ()
    {
        return $this->variantSentCount;
    }

    /**
     * @param $variantSentCount
     *
     * @return $this
     */
    public function setVariantSentCount ($variantSentCount)
    {
        $this->variantSentCount = $variantSentCount;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLists ()
    {
        return $this->lists;
    }

    /**
     * Add list
     *
     * @param LeadList $list
     * @return Email
     */
    public function addList(LeadList $list)
    {
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Remove list
     *
     * @param LeadList $list
     */
    public function removeList(LeadList $list)
    {
        $this->lists->removeElement($list);
    }

    /**
     * @return mixed
     */
    public function getPlainText ()
    {
        return $this->plainText;
    }

    /**
     * @param $plainText
     *
     * @return $this
     */
    public function setPlainText ($plainText)
    {
        $this->plainText = $plainText;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVariantReadCount ()
    {
        return $this->variantReadCount;
    }

    /**
     * @param $variantReadCount
     *
     * @return $this
     */
    public function setVariantReadCount ($variantReadCount)
    {
        $this->variantReadCount = $variantReadCount;

        return $this;
    }

    /**
     * @param bool $isChild True to return if the email is a variant of a parent
     *
     * @return bool
     */
    public function isVariant($isChild = false)
    {
        if ($isChild) {
            return ($this->variantParent === null) ? false : true;
        } else {
            return (!empty($this->variantParent) || count($this->variantChildren)) ? true : false;
        }
    }

    /**
     * @return mixed
     */
    public function getStats ()
    {
        return $this->stats;
    }

    /**
     * @return mixed
     */
    public function getCustomHtml ()
    {
        return $this->customHtml;
    }

    /**
     * @param $customHtml
     *
     * @return $this
     */
    public function setCustomHtml ($customHtml)
    {
        $this->customHtml = $customHtml;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUnsubscribeForm ()
    {
        return $this->unsubscribeForm;
    }

    /**
     * @param Form $unsubscribeForm
     *
     * @return $this
     */
    public function setUnsubscribeForm (Form $unsubscribeForm = null)
    {
        $this->unsubscribeForm = $unsubscribeForm;

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
     * Add asset
     *
     * @param Asset  $asset
     *
     * @return Email
     */
    public function addAssetAttachment(Asset $asset)
    {
        $this->assetAttachments[] = $asset;

        return $this;
    }

    /**
     * Remove asset
     *
     * @param Asset $asset
     */
    public function removeAssetAttachment(Asset $asset)
    {
        $this->assetAttachments->removeElement($asset);
    }

    /**
     * Get assetAttachments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssetAttachments()
    {
        return $this->assetAttachments;
    }
}