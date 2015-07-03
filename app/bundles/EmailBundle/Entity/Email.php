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
use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccess;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Email
 * @ORM\Table(name="emails")
 * @ORM\Entity(repositoryClass="Mautic\EmailBundle\Entity\EmailRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Email extends FormEntity
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $description;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     */
    private $subject;

    /**
     * @ORM\Column(type="string", name="from_address", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $fromAddress;

    /**
     * @ORM\Column(type="string", name="from_name", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $fromName;

    /**
     * @ORM\Column(type="string", name="reply_to_address", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $replyToAddress;

    /**
     * @ORM\Column(type="string", name="bcc_address", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $bccAddress;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $template;

    /**
     * @ORM\Column(name="lang", type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     */
    private $language = 'en';

    /**
     * @ORM\Column(name="content", type="array", nullable=true)
     */
    private $content = array();

    /**
     * @ORM\Column(name="plain_text", type="text", nullable=true)
     */
    private $plainText;

    /**
     * @ORM\Column(name="custom_html", type="text", nullable=true)
     */
    private $customHtml;

    /**
     * @ORM\Column(name="email_type", type="string", nullable=true)
     */
    private $emailType;

    /**
     * @ORM\Column(name="publish_up", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $publishUp;

    /**
     * @ORM\Column(name="publish_down", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $publishDown;

    /**
     * @ORM\Column(name="read_count", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $readCount = 0;

    /**
     * @ORM\Column(name="sent_count", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $sentCount = 0;

    /**
     * @ORM\Column(name="revision", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $revision = 1;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\CategoryBundle\Entity\Category")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     **/
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\LeadBundle\Entity\LeadList", fetch="EXTRA_LAZY", indexBy="id")
     * @ORM\JoinTable(name="email_list_xref")
     * @ORM\JoinColumn(name="list_id", referencedColumnName="id", nullable=true)
     **/
    private $lists;

    /**
     * @ORM\OneToMany(targetEntity="Stat", mappedBy="email", cascade={"persist"}, indexBy="id", fetch="EXTRA_LAZY")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $stats;

    /**
     * @ORM\ManyToOne(targetEntity="Email", inversedBy="variantChildren")
     * @ORM\JoinColumn(name="variant_parent_id", referencedColumnName="id", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $variantParent = null;

    /**
     * @ORM\OneToMany(targetEntity="Email", mappedBy="variantParent", indexBy="id")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     * @Serializer\MaxDepth(1)
     **/
    private $variantChildren;

    /**
     * @ORM\Column(name="variant_settings", type="array", nullable=true)
     */
    private $variantSettings = array();

    /**
     * @ORM\Column(name="variant_start_date", type="datetime", nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $variantStartDate;

    /**
     * @ORM\Column(name="variant_sent_count", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $variantSentCount = 0;

    /**
     * @ORM\Column(name="variant_read_count", type="integer")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $variantReadCount = 0;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\FormBundle\Entity\Form", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="unsubscribeform_id", onDelete="SET NULL")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails"})
     */
    private $unsubscribeForm;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\AssetBundle\Entity\Asset", indexBy="id", fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="email_assets_xref",
     *   joinColumns={@ORM\JoinColumn(name="email_id", referencedColumnName="id")},
     *   inverseJoinColumns={@ORM\JoinColumn(name="asset_id", referencedColumnName="id")}
     * )
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
        $this->id = null;

        parent::__clone();
    }

    public function __construct()
    {
        $this->lists            = new ArrayCollection();
        $this->stats            = new ArrayCollection();
        $this->variantChildren  = new ArrayCollection();
        $this->assetAttachments = new ArrayCollection();
    }

    /**
     *
     */
    public function clearStats()
    {
        $this->stats = new ArrayCollection();
    }

    /**
     *
     */
    public function clearVariants()
    {
        $this->variantChildren = new ArrayCollection();
        $this->variantParent   = null;
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                array(
                    'message' => 'mautic.core.name.required',
                    'groups'  => array('General')
                )
            )
        );

        $metadata->addPropertyConstraint(
            'fromAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required',
                    'groups'  => array('General')
                )
            )
        );

        $metadata->addPropertyConstraint(
            'replyToAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required',
                    'groups'  => array('General')
                )
            )
        );

        $metadata->addPropertyConstraint(
            'bccAddress',
            new \Symfony\Component\Validator\Constraints\Email(
                array(
                    'message' => 'mautic.core.email.required',
                    'groups'  => array('General')
                )
            )
        );

        $metadata->addPropertyConstraint(
            'lists',
            new LeadListAccess(
                array(
                    'message' => 'mautic.lead.lists.required',
                    'groups'  => array('List')
                )
            )
        );

        $metadata->addPropertyConstraint(
            'lists',
            new NotBlank(
                array(
                    'message' => 'mautic.lead.lists.required',
                    'groups'  => array('List')
                )
            )
        );
    }

    /**
     * @param \Symfony\Component\Form\Form $form
     *
     * @return array
     */
    public static function determineValidationGroups(\Symfony\Component\Form\Form $form)
    {
        return ($form->getData()->getEmailType() == 'list') ? array('General', 'List') : array('General');
    }

    /**
     * @param $prop
     * @param $val
     */
    protected function isChanged($prop, $val)
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
    public function getId()
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
        $this->isChanged('content', $content);
        $this->content = $content;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReadCount ()
    {
        return $this->readCount;
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
     * @return Email
     */
    public function addVariantChild(\Mautic\EmailBundle\Entity\Email $variantChildren)
    {
        $this->variantChildren[] = $variantChildren;

        return $this;
    }

    /**
     * Remove variantChildren
     *
     * @param \Mautic\EmailBundle\Entity\Email $variantChildren
     */
    public function removeVariantChild(\Mautic\EmailBundle\Entity\Email $variantChildren)
    {
        $this->variantChildren->removeElement($variantChildren);
    }

    /**
     * Get variantChildren
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVariantChildren()
    {
        return $this->variantChildren;
    }

    /**
     * Set variantParent
     *
     * @param \Mautic\EmailBundle\Entity\Email $variantParent
     * @return Email
     */
    public function setVariantParent(\Mautic\EmailBundle\Entity\Email $variantParent = null)
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
    public function getVariantParent()
    {
        return $this->variantParent;
    }

    /**
     * Set variantSettings
     *
     * @param array $variantSettings
     * @return Email
     */
    public function setVariantSettings($variantSettings)
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
    public function getVariantSettings()
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
     * @return mixed
     */
    public function getSentCount ()
    {
        return $this->sentCount;
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
     * @param \Mautic\LeadBundle\Entity\LeadList $list
     * @return Email
     */
    public function addList(\Mautic\LeadBundle\Entity\LeadList $list)
    {
        $this->lists[] = $list;

        return $this;
    }

    /**
     * Remove list
     *
     * @param \Mautic\LeadBundle\Entity\LeadList $list
     */
    public function removeList(\Mautic\LeadBundle\Entity\LeadList $list)
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
     * Increase sent counts by one
     */
    public function upSentCounts()
    {
        $this->sentCount++;
        if (!empty($this->variantStartDate)) {
            $this->variantSentCount++;
        }
    }

    /**
     * Decrease sent counts by one
     */
    public function downSentCounts()
    {
        if ($this->sentCount) {
            $this->sentCount--;
            if (!empty($this->variantStartDate) && $this->variantSentCount) {
                $this->variantSentCount--;
            }
        }
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
    public function setUnsubscribeForm (Form $unsubscribeForm)
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