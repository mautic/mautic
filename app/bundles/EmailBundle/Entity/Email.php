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
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Mautic\FormBundle\Entity\Form;
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
     * @ORM\Column(type="string")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"emailDetails", "emailList"})
     */
    private $subject;

    /**
     * @ORM\Column(type="string")
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
     * @ORM\Column(name="content_mode", type="string")
     */
    private $contentMode = 'custom';

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
     * Used to identify the page for the builder
     *
     * @var
     */
    private $sessionId;

    public function __clone() {
        $this->id = null;
    }

    public function __construct()
    {
        $this->lists = new ArrayCollection();
        $this->stats = new ArrayCollection();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('subject', new NotBlank(array(
            'message' => 'mautic.email.subject.notblank'
        )));
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
     * @param mixed $category
     */
    public function setCategory ($category)
    {
        $this->isChanged('category', $category);
        $this->category = $category;
    }

    /**
     * @return array
     */
    public function getContent ()
    {
        return $this->content;
    }

    /**
     * @param array $content
     */
    public function setContent ($content)
    {
        $this->isChanged('content', $content);
        $this->content = $content;
    }

    /**
     * @return mixed
     */
    public function getReadCount ()
    {
        return $this->readCount;
    }

    /**
     * @param mixed $readCount
     */
    public function setReadCount ($readCount)
    {
        $this->readCount = $readCount;
    }

    /**
     * @return mixed
     */
    public function getLanguage ()
    {
        return $this->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage ($language)
    {
        $this->isChanged('language', $language);
        $this->language = $language;
    }

    /**
     * @return mixed
     */
    public function getRevision ()
    {
        return $this->revision;
    }

    /**
     * @param mixed $revision
     */
    public function setRevision ($revision)
    {
        $this->revision = $revision;
    }

    /**
     * @return mixed
     */
    public function getSessionId ()
    {
        return $this->sessionId;
    }

    /**
     * @param mixed $sessionId
     */
    public function setSessionId ($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return mixed
     */
    public function getSubject ()
    {
        return $this->subject;
    }

    /**
     * @param mixed $subject
     */
    public function setSubject ($subject)
    {
        $this->isChanged('subject', $subject);
        $this->subject = $subject;
    }

    /**
     * @return mixed
     */
    public function getTemplate ()
    {
        return $this->template;
    }

    /**
     * @param mixed $template
     */
    public function setTemplate ($template)
    {
        $this->isChanged('template', $template);
        $this->template = $template;
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
     * @param mixed $variantStartDate
     */
    public function setVariantStartDate ($variantStartDate)
    {
        $this->isChanged('variantStartDate', $variantStartDate);
        $this->variantStartDate = $variantStartDate;
    }

    /**
     * @return mixed
     */
    public function getPublishDown ()
    {
        return $this->publishDown;
    }

    /**
     * @param mixed $publishDown
     */
    public function setPublishDown ($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);
        $this->publishDown = $publishDown;
    }

    /**
     * @return mixed
     */
    public function getPublishUp ()
    {
        return $this->publishUp;
    }

    /**
     * @param mixed $publishUp
     */
    public function setPublishUp ($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);
        $this->publishUp = $publishUp;
    }

    /**
     * @return mixed
     */
    public function getSentCount ()
    {
        return $this->sentCount;
    }

    /**
     * @param mixed $sentCount
     */
    public function setSentCount ($sentCount)
    {
        $this->sentCount = $sentCount;
    }

    /**
     * @return mixed
     */
    public function getVariantSentCount ()
    {
        return $this->variantSentCount;
    }

    /**
     * @param mixed $variantSentCount
     */
    public function setVariantSentCount ($variantSentCount)
    {
        $this->variantSentCount = $variantSentCount;
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
     * @param mixed $plainText
     */
    public function setPlainText ($plainText)
    {
        $this->plainText = $plainText;
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
     * @param mixed $variantReadCount
     */
    public function setVariantReadCount ($variantReadCount)
    {
        $this->variantReadCount = $variantReadCount;
    }

    /**
     * @return bool
     */
    public function isVariant($parentOnly = false)
    {
        if ($parentOnly) {
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
     * @param mixed $customHtml
     */
    public function setCustomHtml ($customHtml)
    {
        $this->customHtml = $customHtml;
    }

    /**
     * @return mixed
     */
    public function getContentMode ()
    {
        return $this->contentMode;
    }

    /**
     * @param mixed $contentMode
     */
    public function setContentMode ($contentMode)
    {
        $this->contentMode = $contentMode;
    }

    /**
     * @return mixed
     */
    public function getUnsubscribeForm ()
    {
        return $this->unsubscribeForm;
    }

    /**
     * @param mixed $unsubscribeForm
     */
    public function setUnsubscribeForm (Form $unsubscribeForm)
    {
        $this->unsubscribeForm = $unsubscribeForm;
    }
}